<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\UI\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Security manager for tabs with middleware, rate limiting, and content protection
 */
class TabsSecurityManager
{
    /** @var array<string, mixed> */
    private array $config;
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->config = config('laravel-tabs.security', []);
    }

    /**
     * Check if tab switching should be rate limited
     */
    public function checkRateLimit(string $tabId, string $identifier): bool
    {
        if (!($this->config['rate_limiting']['enabled'] ?? false)) {
            return false; // Not rate limited
        }

        $key = $this->generateRateLimitKey($tabId, $identifier);
        $maxAttempts = $this->config['rate_limiting']['attempts'] ?? 60;
        $decayMinutes = $this->config['rate_limiting']['decay'] ?? 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->logSecurityEvent('rate_limit_exceeded', $tabId, [
                'identifier' => $identifier,
                'key' => $key,
                'max_attempts' => $maxAttempts,
            ]);

            return true; // Rate limited
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return false; // Not rate limited
    }

    /**
     * Get remaining rate limit attempts
     */
    public function getRemainingAttempts(string $tabId, string $identifier): int
    {
        if (!($this->config['rate_limiting']['enabled'] ?? false)) {
            return -1; // Unlimited
        }

        $key = $this->generateRateLimitKey($tabId, $identifier);
        $maxAttempts = $this->config['rate_limiting']['attempts'] ?? 60;

        return RateLimiter::retriesLeft($key, $maxAttempts);
    }

    /**
     * Get rate limit reset time
     */
    public function getRateLimitResetTime(string $tabId, string $identifier): ?int
    {
        if (!($this->config['rate_limiting']['enabled'] ?? false)) {
            return null;
        }

        $key = $this->generateRateLimitKey($tabId, $identifier);
        return RateLimiter::availableIn($key);
    }

    /**
     * Sanitize tab content for XSS protection
     */
    public function sanitizeContent(string $content): string
    {
        if (!($this->config['content_security']['sanitize_html'] ?? false)) {
            return $content;
        }

        // Basic XSS protection
        if ($this->config['content_security']['escape_content'] ?? false) {
            return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        // Strip dangerous tags and attributes
        $content = $this->stripDangerousTags($content);
        $content = $this->stripDangerousAttributes($content);

        $this->logSecurityEvent('content_sanitized', '', [
            'original_length' => strlen($content),
            'sanitized_length' => strlen($content),
        ]);

        return $content;
    }

    /**
     * Validate tab access permissions
     * 
     * @return array<string, mixed>
     */
    public function canAccessTab(Tab $tab, ?string $userId = null): array
    {
        $result = [
            'allowed' => true,
            'reason' => null,
            'middleware_passed' => true,
        ];

        // Check if tab is disabled
        if ($tab->isDisabled()) {
            $result['allowed'] = false;
            $result['reason'] = 'Tab is disabled';
            return $result;
        }

        // Check global middleware
        $globalMiddleware = $this->config['middleware'] ?? [];
        foreach ($globalMiddleware as $middleware) {
            if (!$this->checkMiddleware($middleware, $tab, $userId)) {
                $result['allowed'] = false;
                $result['reason'] = "Failed middleware: {$middleware}";
                $result['middleware_passed'] = false;
                break;
            }
        }

        // Check tab-specific permissions (if implemented)
        if ($result['allowed'] && method_exists($tab, 'getPermissions')) {
            $permissions = $tab->getPermissions();
            if (!empty($permissions) && !$this->checkPermissions($permissions, $userId)) {
                $result['allowed'] = false;
                $result['reason'] = 'Insufficient permissions';
            }
        }

        if (!$result['allowed']) {
            $this->logSecurityEvent('tab_access_denied', $tab->getId(), [
                'user_id' => $userId,
                'reason' => $result['reason'],
            ]);
        }

        return $result;
    }

    /**
     * Generate CSRF token for tab operations
     */
    public function generateCsrfToken(string $tabId): string
    {
        if (!($this->config['csrf'] ?? true)) {
            return '';
        }

        return csrf_token() . ':' . hash('sha256', $tabId . session()->getId());
    }

    /**
     * Verify CSRF token for tab operations
     */
    public function verifyCsrfToken(string $token, string $tabId): bool
    {
        if (!($this->config['csrf'] ?? true)) {
            return true; // CSRF disabled
        }

        if (empty($token)) {
            $this->logSecurityEvent('csrf_token_missing', $tabId);
            return false;
        }

        $parts = explode(':', $token, 2);
        if (count($parts) !== 2) {
            $this->logSecurityEvent('csrf_token_invalid_format', $tabId);
            return false;
        }

        [$csrfToken, $tabHash] = $parts;

        // Verify Laravel CSRF token
        if ($csrfToken !== csrf_token()) {
            $this->logSecurityEvent('csrf_token_mismatch', $tabId);
            return false;
        }

        // Verify tab-specific hash
        $expectedHash = hash('sha256', $tabId . session()->getId());
        if (!hash_equals($expectedHash, $tabHash)) {
            $this->logSecurityEvent('csrf_tab_hash_mismatch', $tabId);
            return false;
        }

        return true;
    }

    /**
     * Get security headers for tab responses
     * 
     * @return array<string, string>
     */
    public function getSecurityHeaders(): array
    {
        $headers = [];

        if ($this->config['xss_protection'] ?? true) {
            $headers['X-XSS-Protection'] = '1; mode=block';
            $headers['X-Content-Type-Options'] = 'nosniff';
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
        }

        // Content Security Policy for tab content
        $csp = $this->generateContentSecurityPolicy();
        if ($csp) {
            $headers['Content-Security-Policy'] = $csp;
        }

        return $headers;
    }

    /**
     * Validate and sanitize tab ID
     * 
     * @throws \InvalidArgumentException When tab ID is invalid after sanitization
     */
    public function sanitizeTabId(string $tabId): string
    {
        // Remove any potentially dangerous characters
        $tabId = preg_replace('/[^a-zA-Z0-9_-]/', '', $tabId);

        // Limit length
        $tabId = substr($tabId, 0, 50);

        if (empty($tabId)) {
            throw new \InvalidArgumentException('Invalid tab ID provided');
        }

        return $tabId;
    }

    /**
     * Get security configuration for frontend
     * 
     * @return array<string, mixed>
     */
    public function getSecurityConfig(): array
    {
        return [
            'csrf_enabled' => $this->config['csrf'] ?? true,
            'rate_limiting' => [
                'enabled' => $this->config['rate_limiting']['enabled'] ?? false,
                'attempts' => $this->config['rate_limiting']['attempts'] ?? 60,
                'decay' => $this->config['rate_limiting']['decay'] ?? 1,
            ],
            'xss_protection' => $this->config['xss_protection'] ?? true,
        ];
    }

    /**
     * Generate JavaScript for client-side security
     */
    public function generateSecurityJavaScript(): string
    {
        $config = $this->getSecurityConfig();
        $configJson = json_encode($config);

        return "
            window.TabsSecurity = {
                config: {$configJson},

                // Check if we're being rate limited
                checkRateLimit: function(tabId) {
                    if (!this.config.rate_limiting.enabled) {
                        return { limited: false };
                    }

                    const key = 'tabs_rate_limit_' + tabId;
                    const attempts = parseInt(localStorage.getItem(key) || '0');
                    const resetTime = parseInt(localStorage.getItem(key + '_reset') || '0');

                    // Check if reset time has passed
                    if (Date.now() > resetTime) {
                        localStorage.removeItem(key);
                        localStorage.removeItem(key + '_reset');
                        return { limited: false };
                    }

                    if (attempts >= this.config.rate_limiting.attempts) {
                        return {
                            limited: true,
                            resetTime: resetTime,
                            remaining: 0
                        };
                    }

                    return {
                        limited: false,
                        remaining: this.config.rate_limiting.attempts - attempts
                    };
                },

                // Record a tab switch attempt
                recordAttempt: function(tabId) {
                    if (!this.config.rate_limiting.enabled) {
                        return;
                    }

                    const key = 'tabs_rate_limit_' + tabId;
                    const attempts = parseInt(localStorage.getItem(key) || '0') + 1;
                    const resetTime = Date.now() + (this.config.rate_limiting.decay * 60 * 1000);

                    localStorage.setItem(key, attempts.toString());
                    localStorage.setItem(key + '_reset', resetTime.toString());
                },

                // Sanitize content for XSS protection
                sanitizeContent: function(content) {
                    if (!this.config.xss_protection) {
                        return content;
                    }

                    // Basic client-side XSS protection
                    const div = document.createElement('div');
                    div.textContent = content;
                    return div.innerHTML;
                },

                // Generate CSRF token for requests
                getCsrfToken: function(tabId) {
                    if (!this.config.csrf_enabled) {
                        return null;
                    }

                    const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.warn('CSRF token not found in meta tag');
                        return null;
                    }

                    return csrfToken;
                }
            };
        ";
    }

    // Private helper methods

    private function generateRateLimitKey(string $tabId, string $identifier): string
    {
        $keyType = $this->config['rate_limiting']['key'] ?? 'ip';

        switch ($keyType) {
            case 'user':
                $key = auth()->id() ?: $this->request->ip();
                break;
            case 'session':
                $key = session()->getId();
                break;
            case 'ip':
            default:
                $key = $this->request->ip();
                break;
        }

        return "tabs:{$tabId}:{$key}";
    }

    private function stripDangerousTags(string $content): string
    {
        $dangerousTags = [
            'script',
            'iframe',
            'object',
            'embed',
            'form',
            'input',
            'textarea',
            'select',
            'button',
        ];

        foreach ($dangerousTags as $tag) {
            $content = preg_replace("/<\/?{$tag}[^>]*>/i", '', $content);
        }

        return $content;
    }

    private function stripDangerousAttributes(string $content): string
    {
        $dangerousAttributes = [
            'onload',
            'onclick',
            'onmouseover',
            'onerror',
            'onabort',
            'onblur',
            'onchange',
            'onfocus',
            'onkeydown',
            'onkeyup',
            'javascript:',
            'vbscript:',
            'data:',
            'on\w+',
        ];

        foreach ($dangerousAttributes as $attr) {
            $content = preg_replace("/{$attr}=['\"][^'\"]*['\"]/i", '', $content);
        }

        return $content;
    }

    private function checkMiddleware(string $middleware, Tab $tab, ?string $userId): bool
    {
        // This is a simplified middleware check
        // In a real implementation, you would integrate with Laravel's middleware system

        switch ($middleware) {
            case 'auth':
                return $userId !== null;
            case 'guest':
                return $userId === null;
            default:
                // For custom middleware, you could call the actual middleware
                return true;
        }
    }

    /**
     * @param array<string> $permissions
     */
    private function checkPermissions(array $permissions, ?string $userId): bool
    {
        // This is a simplified permission check
        // In a real implementation, you would integrate with your authorization system

        if (!$userId) {
            return false;
        }

        // Example permission check - adapt to your needs
        foreach ($permissions as $permission) {
            if (!auth()->user()?->can($permission)) {
                return false;
            }
        }

        return true;
    }

    private function generateContentSecurityPolicy(): ?string
    {
        if (!($this->config['xss_protection'] ?? true)) {
            return null;
        }

        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ];

        return implode('; ', $policies);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logSecurityEvent(string $event, string $tabId, array $context = []): void
    {
        Log::warning("Tabs Security: {$event}", array_merge([
            'tab_id' => $tabId,
            'event' => $event,
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ], $context));
    }
}
