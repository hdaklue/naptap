<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\UI\Tab;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Advanced caching system for tabs content and state
 * Optimized for Livewire's state-heavy operations
 */
class TabsCacheManager
{
    private Repository $cache;
    private array $config;
    private string $prefix;

    public function __construct()
    {
        $this->config = config('laravel-tabs.performance.content_caching', []);
        $this->prefix = $this->config['prefix'] ?? 'tabs';

        // Use configured cache driver
        $driver = $this->config['driver'] ?? 'file';
        $this->cache = Cache::store($driver);
    }

    /**
     * Cache tab content with intelligent key generation
     */
    public function cacheContent(string $tabId, string $content, null|string $userId = null): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        $key = $this->generateContentKey($tabId, $userId);
        $ttl = $this->config['ttl'] ?? 300;

        $cacheData = [
            'content' => $content,
            'cached_at' => now()->timestamp,
            'tab_id' => $tabId,
            'user_id' => $userId,
            'hash' => $this->generateContentHash($content),
        ];

        if ($this->config['serialize'] ?? true) {
            $cacheData = serialize($cacheData);
        }

        // Use cache tags for selective clearing if supported
        if ($this->supportsTags()) {
            $tags = $this->generateTags($tabId, $userId);
            $this->cache->tags($tags)->put($key, $cacheData, $ttl);
        } else {
            $this->cache->put($key, $cacheData, $ttl);
        }

        $this->logCacheOperation('content_cached', $key, ['tab_id' => $tabId, 'ttl' => $ttl]);
    }

    /**
     * Retrieve cached tab content
     */
    public function getCachedContent(string $tabId, null|string $userId = null): null|string
    {
        if (!$this->isCachingEnabled()) {
            return null;
        }

        $key = $this->generateContentKey($tabId, $userId);

        try {
            $cached = $this->supportsTags()
                ? $this->cache->tags($this->generateTags($tabId, $userId))->get($key)
                : $this->cache->get($key);

            if ($cached === null) {
                return null;
            }

            if ($this->config['serialize'] ?? true) {
                $cached = unserialize($cached);
            }

            $this->logCacheOperation('content_hit', $key, ['tab_id' => $tabId]);

            return $cached['content'] ?? null;
        } catch (\Exception $e) {
            $this->logCacheError('content_retrieval_failed', $e, ['tab_id' => $tabId]);
            return null;
        }
    }

    /**
     * Cache component state for faster re-rendering
     */
    public function cacheComponentState(string $componentId, array $state, null|string $userId = null): void
    {
        if (!$this->isStateCachingEnabled()) {
            return;
        }

        $key = $this->generateStateKey($componentId, $userId);
        $ttl = config('laravel-tabs.performance.state_caching.ttl', 60);

        $stateData = [
            'state' => $state,
            'cached_at' => now()->timestamp,
            'component_id' => $componentId,
            'user_id' => $userId,
        ];

        $this->cache->put($key, serialize($stateData), $ttl);
        $this->logCacheOperation('state_cached', $key, ['component_id' => $componentId]);
    }

    /**
     * Retrieve cached component state
     */
    public function getCachedComponentState(string $componentId, null|string $userId = null): null|array
    {
        if (!$this->isStateCachingEnabled()) {
            return null;
        }

        $key = $this->generateStateKey($componentId, $userId);

        try {
            $cached = $this->cache->get($key);

            if ($cached === null) {
                return null;
            }

            $stateData = unserialize($cached);
            $this->logCacheOperation('state_hit', $key, ['component_id' => $componentId]);

            return $stateData['state'] ?? null;
        } catch (\Exception $e) {
            $this->logCacheError('state_retrieval_failed', $e, ['component_id' => $componentId]);
            return null;
        }
    }

    /**
     * Invalidate tab content cache
     */
    public function invalidateTabContent(string $tabId, null|string $userId = null): bool
    {
        try {
            if ($this->supportsTags()) {
                $tags = $this->generateTags($tabId, $userId);
                return $this->cache->tags($tags)->flush();
            } else {
                $key = $this->generateContentKey($tabId, $userId);
                return $this->cache->forget($key);
            }
        } catch (\Exception $e) {
            $this->logCacheError('invalidation_failed', $e, ['tab_id' => $tabId]);
            return false;
        }
    }

    /**
     * Clear all tab caches
     */
    public function clearAllCaches(): bool
    {
        try {
            if ($this->supportsTags()) {
                return $this->cache->tags([$this->prefix])->flush();
            } else {
                // Fallback: clear by pattern (if supported by cache driver)
                return $this->clearByPattern("{$this->prefix}:*");
            }
        } catch (\Exception $e) {
            $this->logCacheError('clear_all_failed', $e);
            return false;
        }
    }

    /**
     * Get cache statistics and health info
     */
    public function getCacheStats(): array
    {
        $stats = [
            'enabled' => $this->isCachingEnabled(),
            'driver' => $this->config['driver'] ?? 'file',
            'prefix' => $this->prefix,
            'supports_tags' => $this->supportsTags(),
            'content_ttl' => $this->config['ttl'] ?? 300,
            'state_ttl' => config('laravel-tabs.performance.state_caching.ttl', 60),
        ];

        // Add driver-specific stats if available
        try {
            if (method_exists($this->cache, 'getStore')) {
                $store = $this->cache->getStore();
                if (method_exists($store, 'connection')) {
                    $stats['connection'] = get_class($store->connection());
                }
            }
        } catch (\Exception $e) {
            // Ignore connection info errors
        }

        return $stats;
    }

    /**
     * Preload cache for adjacent tabs
     */
    public function preloadAdjacentTabs(array $tabIds, string $currentTabId, null|string $userId = null): void
    {
        if (!config('laravel-tabs.performance.preload_adjacent', false)) {
            return;
        }

        $currentIndex = array_search($currentTabId, $tabIds);
        if ($currentIndex === false) {
            return;
        }

        $toPreload = [];

        // Previous tab
        if ($currentIndex > 0) {
            $toPreload[] = $tabIds[$currentIndex - 1];
        }

        // Next tab
        if ($currentIndex < (count($tabIds) - 1)) {
            $toPreload[] = $tabIds[$currentIndex + 1];
        }

        foreach ($toPreload as $tabId) {
            // Only preload if not already cached
            if ($this->getCachedContent($tabId, $userId) === null) {
                $this->schedulePreload($tabId, $userId);
            }
        }
    }

    // Private helper methods

    private function generateContentKey(string $tabId, null|string $userId = null): string
    {
        $key = "{$this->prefix}:content:{$tabId}";

        if ($userId && ($this->config['per_user'] ?? false)) {
            $key .= ":user:{$userId}";
        }

        return $key;
    }

    private function generateStateKey(string $componentId, null|string $userId = null): string
    {
        $key = "{$this->prefix}:state:{$componentId}";

        if ($userId && config('laravel-tabs.performance.state_caching.per_user', true)) {
            $key .= ":user:{$userId}";
        }

        return $key;
    }

    private function generateTags(string $tabId, null|string $userId = null): array
    {
        $tags = [$this->prefix, "tab:{$tabId}"];

        if ($userId) {
            $tags[] = "user:{$userId}";
        }

        return $tags;
    }

    private function generateContentHash(string $content): string
    {
        return hash('sha256', $content);
    }

    private function supportsTags(): bool
    {
        return (
            ($this->config['tags'] ?? true)
            && method_exists($this->cache, 'tags')
            && in_array($this->config['driver'] ?? 'file', ['redis', 'memcached', 'dynamodb'])
        );
    }

    private function isCachingEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    private function isStateCachingEnabled(): bool
    {
        return config('laravel-tabs.performance.state_caching.enabled', true);
    }

    private function clearByPattern(string $pattern): bool
    {
        // Implementation depends on cache driver
        // This is a simplified version
        return true;
    }

    private function schedulePreload(string $tabId, null|string $userId = null): void
    {
        // Could use Laravel queues for background preloading
        // For now, just log the preload intention
        $this->logCacheOperation('preload_scheduled', '', ['tab_id' => $tabId]);
    }

    private function logCacheOperation(string $operation, string $key, array $context = []): void
    {
        if (config('laravel-tabs.development.debug_mode', false)) {
            Log::debug("Tabs Cache: {$operation}", array_merge([
                'key' => $key,
                'timestamp' => now()->toISOString(),
            ], $context));
        }
    }

    private function logCacheError(string $operation, \Exception $e, array $context = []): void
    {
        if (config('laravel-tabs.error_handling.log_errors', true)) {
            Log::error("Tabs Cache Error: {$operation}", array_merge([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $context));
        }
    }
}
