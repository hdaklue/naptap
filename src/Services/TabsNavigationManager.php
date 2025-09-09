<?php

declare(strict_types=1);

namespace Hdaklu\NapTab\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Hdaklu\NapTab\UI\Tab;

/**
 * Navigation manager for different tab navigation modes and URL strategies
 * Handles spa, navigate, and reload modes with path/query URL strategies
 */
class TabsNavigationManager
{
    private array $config;
    private Request $request;
    private string $mode;
    private string $urlStrategy;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->config = config('laravel-tabs.navigation', []);
        $this->mode = $this->config['mode'] ?? 'spa';
        $this->urlStrategy = $this->config['url_strategy'] ?? 'path';
    }

    /**
     * Determine the active tab based on current URL and navigation mode
     */
    public function getActiveTabFromUrl(array $availableTabs, ?string $defaultTab = null): ?string
    {
        $activeTab = null;

        switch ($this->urlStrategy) {
            case 'path':
                $activeTab = $this->getActiveTabFromPath();
                break;
            case 'query':
                $activeTab = $this->getActiveTabFromQuery();
                break;
        }

        // Validate that the tab exists
        $tabIds = array_map(fn(Tab $tab) => $tab->getId(), $availableTabs);
        
        if ($activeTab && !in_array($activeTab, $tabIds)) {
            $this->logNavigation('invalid_tab_requested', $activeTab, [
                'available_tabs' => $tabIds,
                'url' => $this->request->fullUrl(),
            ]);
            $activeTab = null;
        }

        // Fall back to remembered tab or default
        if (!$activeTab) {
            $activeTab = $this->getRememberedTab() ?? $this->getDefaultTab($availableTabs, $defaultTab);
        }

        return $activeTab;
    }

    /**
     * Generate URL for a specific tab based on current strategy
     */
    public function generateTabUrl(string $tabId, array $context = []): string
    {
        // Try to use named routes first, then fall back to URL generation
        if ($this->mode === 'spa') {
            // For SPA mode, just return current URL with tab parameter
            return $this->request->fullUrl();
        }
        
        switch ($this->urlStrategy) {
            case 'path':
                return $this->generatePathUrl($tabId, $context);
            case 'query':
                return $this->generateQueryUrl($tabId, $context);
            default:
                return $this->request->url();
        }
    }

    /**
     * Get navigation attributes for frontend JavaScript
     */
    public function getNavigationAttributes(string $tabId): array
    {
        $url = $this->generateTabUrl($tabId);
        
        $attributes = [
            'data-tab-id' => $tabId,
            'data-tab-url' => $url,
            'data-navigation-mode' => $this->mode,
        ];

        switch ($this->mode) {
            case 'navigate':
                $attributes['wire:navigate'] = true;
                $attributes['href'] = $url;
                break;
            case 'reload':
                $attributes['href'] = $url;
                break;
            case 'spa':
            default:
                // SPA mode uses JavaScript for navigation
                $attributes['data-spa-url'] = $url;
                break;
        }

        return $attributes;
    }

    /**
     * Handle tab switch based on navigation mode
     */
    public function handleTabSwitch(string $newTabId, string $componentId, array $context = []): array
    {
        $startTime = microtime(true);
        
        // Remember the tab if enabled
        if ($this->config['remember_tab'] ?? false) {
            $this->rememberTab($newTabId, $componentId);
        }

        // Update browser history if enabled
        $updateHistory = $this->config['browser_history'] ?? true;
        $url = $updateHistory ? $this->generateTabUrl($newTabId, $context) : null;

        $result = [
            'tab_id' => $newTabId,
            'url' => $url,
            'mode' => $this->mode,
            'update_history' => $updateHistory,
            'deep_linking' => $this->config['deep_linking'] ?? true,
            'switch_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ];

        // Add mode-specific handling
        switch ($this->mode) {
            case 'navigate':
                $result['wire_navigate'] = true;
                $result['redirect_url'] = $url;
                break;
            case 'reload':
                $result['reload_required'] = true;
                $result['redirect_url'] = $url;
                break;
            case 'spa':
                $result['spa_navigation'] = true;
                $result['history_state'] = [
                    'tab_id' => $newTabId,
                    'component_id' => $componentId,
                    'timestamp' => now()->timestamp,
                ];
                break;
        }

        $this->logNavigation('tab_switch_handled', $newTabId, $result);

        return $result;
    }

    /**
     * Generate JavaScript for navigation handling
     */
    public function generateNavigationJavaScript(): string
    {
        $config = [
            'mode' => $this->mode,
            'url_strategy' => $this->urlStrategy,
            'browser_history' => $this->config['browser_history'] ?? true,
            'deep_linking' => $this->config['deep_linking'] ?? true,
        ];

        $configJson = json_encode($config);

        return "
            window.TabsNavigation = {
                config: {$configJson},
                
                // Handle tab navigation based on mode
                navigateToTab: function(tabId, url, mode) {
                    mode = mode || this.config.mode;
                    
                    switch (mode) {
                        case 'navigate':
                            if (window.Livewire) {
                                window.Livewire.navigate(url);
                            } else {
                                window.location.href = url;
                            }
                            break;
                            
                        case 'reload':
                            window.location.href = url;
                            break;
                            
                        case 'spa':
                        default:
                            this.handleSpaNavigation(tabId, url);
                            break;
                    }
                },
                
                // Handle SPA navigation with history management
                handleSpaNavigation: function(tabId, url) {
                    if (this.config.browser_history && url) {
                        const state = {
                            tab_id: tabId,
                            timestamp: Date.now()
                        };
                        
                        if (window.location.href !== url) {
                            window.history.pushState(state, '', url);
                        }
                    }
                    
                    // Dispatch custom event for components to handle
                    const event = new CustomEvent('tabs:navigate', {
                        detail: { tabId, url, mode: 'spa' }
                    });
                    document.dispatchEvent(event);
                },
                
                // Handle browser back/forward navigation
                handlePopState: function(event) {
                    if (event.state && event.state.tab_id) {
                        const event = new CustomEvent('tabs:popstate', {
                            detail: { 
                                tabId: event.state.tab_id,
                                state: event.state
                            }
                        });
                        document.dispatchEvent(event);
                    }
                },
                
                // Initialize navigation handling
                init: function() {
                    if (this.config.browser_history) {
                        window.addEventListener('popstate', this.handlePopState.bind(this));
                    }
                    
                    // Handle initial deep linking
                    if (this.config.deep_linking) {
                        this.handleDeepLinking();
                    }
                },
                
                // Handle deep linking on page load
                handleDeepLinking: function() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const pathSegments = window.location.pathname.split('/').filter(Boolean);
                    
                    let targetTab = null;
                    
                    if (this.config.url_strategy === 'query') {
                        targetTab = urlParams.get('tab');
                    } else if (this.config.url_strategy === 'path') {
                        // Extract tab from path (e.g., /tabs/profile)
                        const tabIndex = pathSegments.indexOf('tabs');
                        if (tabIndex !== -1 && pathSegments[tabIndex + 1]) {
                            targetTab = pathSegments[tabIndex + 1];
                        }
                    }
                    
                    if (targetTab) {
                        const event = new CustomEvent('tabs:deep-link', {
                            detail: { tabId: targetTab }
                        });
                        document.dispatchEvent(event);
                    }
                }
            };
            
            // Initialize navigation when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (window.TabsNavigation) {
                    window.TabsNavigation.init();
                }
            });
        ";
    }

    /**
     * Get URL strategy configuration for components
     */
    public function getUrlStrategyConfig(): array
    {
        return [
            'strategy' => $this->urlStrategy,
            'mode' => $this->mode,
            'browser_history' => $this->config['browser_history'] ?? true,
            'deep_linking' => $this->config['deep_linking'] ?? true,
            'remember_tab' => $this->config['remember_tab'] ?? false,
            'base_path' => $this->getBasePath(),
        ];
    }

    /**
     * Validate navigation configuration
     */
    public function validateConfig(): array
    {
        $issues = [];

        if (!in_array($this->mode, ['spa', 'navigate', 'reload'])) {
            $issues[] = "Invalid navigation mode: {$this->mode}";
        }

        if (!in_array($this->urlStrategy, ['path', 'query'])) {
            $issues[] = "Invalid URL strategy: {$this->urlStrategy}";
        }

        if ($this->mode === 'navigate' && !class_exists('\\Livewire\\Livewire')) {
            $issues[] = "Navigate mode requires Livewire to be installed";
        }

        return $issues;
    }

    // Private helper methods

    private function getActiveTabFromPath(): ?string
    {
        $path = $this->request->path();
        $segments = explode('/', trim($path, '/'));
        
        // Check for route parameter structure like 'demo-tabs/profile' or 'settings/profile'
        if (count($segments) >= 2) {
            $lastSegment = end($segments);
            // Return the last segment as the potential tab ID
            return $lastSegment;
        }

        return null;
    }

    private function getActiveTabFromQuery(): ?string
    {
        return $this->request->query('tab');
    }

    private function generatePathUrl(string $tabId, array $context = []): string
    {
        $basePath = $this->getBasePath();
        
        // Check if we're already on a route with tab parameter structure
        $currentPath = $this->request->path();
        if (preg_match('#^(.+)/([^/]+)$#', $currentPath, $matches)) {
            // We're on a route like 'demo-tabs/profile', so use same structure
            $url = $matches[1] . '/' . $tabId;
        } else {
            // Fallback to current path + tab
            $url = rtrim($basePath, '/') . '/' . $tabId;
        }
        
        // Add query parameters if provided
        if (!empty($context['query'])) {
            $url .= '?' . http_build_query($context['query']);
        }

        return $url;
    }

    private function generateQueryUrl(string $tabId, array $context = []): string
    {
        $url = $this->request->url();
        $query = $this->request->query();
        
        $query['tab'] = $tabId;
        
        // Merge additional query parameters
        if (!empty($context['query'])) {
            $query = array_merge($query, $context['query']);
        }

        return $url . '?' . http_build_query($query);
    }

    private function getBasePath(): string
    {
        $path = $this->request->path();
        
        // Remove tab-specific segments
        $segments = explode('/', trim($path, '/'));
        $tabIndex = array_search('tabs', $segments);
        
        if ($tabIndex !== false) {
            $segments = array_slice($segments, 0, $tabIndex);
        }
        
        return '/' . implode('/', $segments);
    }

    private function getRememberedTab(): ?string
    {
        if (!($this->config['remember_tab'] ?? false)) {
            return null;
        }

        $sessionKey = $this->getSessionKey();
        return Session::get($sessionKey);
    }

    private function rememberTab(string $tabId, string $componentId): void
    {
        $sessionKey = $this->getSessionKey($componentId);
        Session::put($sessionKey, $tabId);
    }

    private function getSessionKey(string $componentId = 'default'): string
    {
        return "tabs_active_{$componentId}";
    }

    private function getDefaultTab(array $availableTabs, ?string $defaultTab = null): ?string
    {
        if ($defaultTab) {
            return $defaultTab;
        }

        $configDefault = $this->config['default_tab'] ?? 'first';
        
        switch ($configDefault) {
            case 'first':
                return $availableTabs[0]->getId() ?? null;
            case 'last':
                return end($availableTabs)->getId() ?? null;
            default:
                // Assume it's a specific tab ID
                $tabIds = array_map(fn(Tab $tab) => $tab->getId(), $availableTabs);
                return in_array($configDefault, $tabIds) ? $configDefault : null;
        }
    }

    private function logNavigation(string $event, string $tabId, array $context = []): void
    {
        if (config('laravel-tabs.development.debug_mode', false)) {
            Log::debug("Tabs Navigation: {$event}", array_merge([
                'tab_id' => $tabId,
                'mode' => $this->mode,
                'url_strategy' => $this->urlStrategy,
                'timestamp' => now()->toISOString(),
            ], $context));
        }
    }
}