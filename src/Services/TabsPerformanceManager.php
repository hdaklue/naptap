<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\TabsCacheManager;
use App\UI\Tab;
use Illuminate\Support\Facades\Log;

/**
 * Performance optimization manager for tabs
 * Handles debouncing, preloading, and performance monitoring
 */
class TabsPerformanceManager
{
    private TabsCacheManager $cacheManager;
    private array $config;
    private array $loadTimes = [];
    private array $debounceTracker = [];

    public function __construct(TabsCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
        $this->config = config('laravel-tabs.performance', []);
    }

    /**
     * Check if tab switch should be debounced
     */
    public function shouldDebounceTabSwitch(string $tabId, string $sessionId): bool
    {
        $debounceMs = $this->config['debounce_ms'] ?? 100;

        if ($debounceMs <= 0) {
            return false;
        }

        $key = "{$sessionId}:{$tabId}";
        $now = microtime(true) * 1000; // Convert to milliseconds

        if (isset($this->debounceTracker[$key])) {
            $timeDiff = $now - $this->debounceTracker[$key];

            if ($timeDiff < $debounceMs) {
                $this->logPerformance('tab_switch_debounced', $tabId, [
                    'debounce_ms' => $debounceMs,
                    'time_diff' => round($timeDiff, 2),
                ]);
                return true;
            }
        }

        $this->debounceTracker[$key] = $now;
        return false;
    }

    /**
     * Preload adjacent tabs for better user experience
     */
    public function preloadAdjacentTabs(array $allTabs, string $currentTabId, null|string $userId = null): void
    {
        if (!($this->config['preload_adjacent'] ?? false)) {
            return;
        }

        $tabIds = array_map(fn(Tab $tab) => $tab->getId(), $allTabs);
        $currentIndex = array_search($currentTabId, $tabIds);

        if ($currentIndex === false) {
            return;
        }

        $toPreload = [];

        // Previous tab
        if ($currentIndex > 0) {
            $toPreload[] = [
                'tab' => $allTabs[$currentIndex - 1],
                'priority' => 'high', // User likely to go back
            ];
        }

        // Next tab
        if ($currentIndex < (count($allTabs) - 1)) {
            $toPreload[] = [
                'tab' => $allTabs[$currentIndex + 1],
                'priority' => 'medium', // User likely to go forward
            ];
        }

        foreach ($toPreload as $item) {
            $this->preloadTab($item['tab'], $userId, $item['priority']);
        }
    }

    /**
     * Preload a specific tab
     */
    public function preloadTab(Tab $tab, null|string $userId = null, string $priority = 'low'): void
    {
        $tabId = $tab->getId();

        // Skip if already cached
        if ($this->cacheManager->getCachedContent($tabId, $userId) !== null) {
            return;
        }

        // Skip disabled tabs
        if ($tab->isDisabled()) {
            return;
        }

        $this->logPerformance('tab_preload_started', $tabId, [
            'priority' => $priority,
            'user_id' => $userId,
        ]);

        try {
            $startTime = microtime(true);

            // Generate content
            $content = $this->generateTabContent($tab);

            $loadTime = (microtime(true) - $startTime) * 1000;

            // Cache the preloaded content
            $this->cacheManager->cacheContent($tabId, $content, $userId);

            $this->logPerformance('tab_preload_completed', $tabId, [
                'load_time_ms' => round($loadTime, 2),
                'content_length' => strlen($content),
                'priority' => $priority,
            ]);
        } catch (\Exception $e) {
            $this->logPerformance('tab_preload_failed', $tabId, [
                'error' => $e->getMessage(),
                'priority' => $priority,
            ]);
        }
    }

    /**
     * Monitor and track tab loading performance
     */
    public function trackTabLoadPerformance(string $tabId, float $startTime, float $endTime, int $contentLength): array
    {
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $metrics = [
            'tab_id' => $tabId,
            'load_time_ms' => round($loadTime, 2),
            'content_length' => $contentLength,
            'timestamp' => now(),
            'memory_usage' => memory_get_peak_usage(true),
        ];

        // Store for performance analysis
        $this->loadTimes[$tabId] = $metrics;

        // Log performance issues
        if ($loadTime > 1000) { // Slow load (>1s)
            $this->logPerformance('tab_load_slow', $tabId, $metrics);
        } elseif ($loadTime > 500) { // Moderate load (>500ms)
            $this->logPerformance('tab_load_moderate', $tabId, $metrics);
        }

        // Clean old tracking data to prevent memory leaks
        $this->cleanupOldTrackingData();

        return $metrics;
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        $stats = [
            'config' => [
                'lazy_loading' => $this->config['lazy_loading'] ?? true,
                'preload_adjacent' => $this->config['preload_adjacent'] ?? false,
                'debounce_ms' => $this->config['debounce_ms'] ?? 100,
                'caching_enabled' => $this->config['content_caching']['enabled'] ?? true,
            ],
            'runtime' => [
                'tracked_tabs' => count($this->loadTimes),
                'debounce_entries' => count($this->debounceTracker),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
            ],
        ];

        if (!empty($this->loadTimes)) {
            $loadTimes = array_column($this->loadTimes, 'load_time_ms');

            $stats['performance'] = [
                'average_load_time' => round(array_sum($loadTimes) / count($loadTimes), 2),
                'min_load_time' => round(min($loadTimes), 2),
                'max_load_time' => round(max($loadTimes), 2),
                'slow_loads' => count(array_filter($loadTimes, fn($time) => $time > 1000)),
                'fast_loads' => count(array_filter($loadTimes, fn($time) => $time < 200)),
            ];
        }

        return $stats;
    }

    /**
     * Optimize tab switching for better performance
     */
    public function optimizeTabSwitch(
        string $fromTabId,
        string $toTabId,
        array $allTabs,
        null|string $userId = null,
    ): array {
        $startTime = microtime(true);
        $optimizations = [];

        // 1. Check cache first
        $cachedContent = $this->cacheManager->getCachedContent($toTabId, $userId);
        if ($cachedContent !== null) {
            $optimizations[] = 'content_served_from_cache';
        }

        // 2. Preload adjacent tabs after switch
        if ($this->config['preload_adjacent'] ?? false) {
            $this->preloadAdjacentTabs($allTabs, $toTabId, $userId);
            $optimizations[] = 'adjacent_tabs_preloaded';
        }

        // 3. Clean up old cached content if memory is getting high
        if (memory_get_usage(true) > (512 * 1024 * 1024)) { // 512MB
            $this->cleanupMemory();
            $optimizations[] = 'memory_cleanup_performed';
        }

        $switchTime = (microtime(true) - $startTime) * 1000;

        $this->logPerformance('tab_switch_optimized', $toTabId, [
            'from_tab' => $fromTabId,
            'optimizations' => $optimizations,
            'switch_time_ms' => round($switchTime, 2),
        ]);

        return [
            'optimizations_applied' => $optimizations,
            'switch_time_ms' => round($switchTime, 2),
            'cache_hit' => $cachedContent !== null,
        ];
    }

    /**
     * Generate JavaScript code for client-side performance optimizations
     */
    public function generateJavaScriptOptimizations(): string
    {
        $config = [
            'debounce_ms' => $this->config['debounce_ms'] ?? 100,
            'preload_adjacent' => $this->config['preload_adjacent'] ?? false,
            'performance_monitoring' => config('laravel-tabs.development.performance_monitoring', false),
        ];

        $configJson = json_encode($config);

        return "
            window.TabsPerformance = {
                config: {$configJson},
                metrics: {},
                debounceTimers: {},

                // Debounce tab switching to prevent rapid clicks
                debounceTabSwitch: function(tabId, callback) {
                    if (this.debounceTimers[tabId]) {
                        clearTimeout(this.debounceTimers[tabId]);
                    }

                    this.debounceTimers[tabId] = setTimeout(() => {
                        callback();
                        delete this.debounceTimers[tabId];
                    }, this.config.debounce_ms);
                },

                // Track tab load performance
                trackLoadTime: function(tabId, startTime) {
                    const endTime = performance.now();
                    const loadTime = endTime - startTime;

                    this.metrics[tabId] = {
                        loadTime: loadTime,
                        timestamp: new Date().toISOString()
                    };

                    if (this.config.performance_monitoring) {
                        console.log('Tab Load Performance:', {
                            tabId: tabId,
                            loadTime: Math.round(loadTime) + 'ms'
                        });
                    }

                    return loadTime;
                },

                // Preload content using Intersection Observer
                setupIntersectionObserver: function() {
                    if (!this.config.preload_adjacent || !window.IntersectionObserver) {
                        return;
                    }

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                const tabId = entry.target.dataset.tabId;
                                if (tabId) {
                                    this.preloadTab(tabId);
                                }
                            }
                        });
                    }, { rootMargin: '50px' });

                    document.querySelectorAll('[data-tab-id]').forEach((el) => {
                        observer.observe(el);
                    });
                },

                // Preload adjacent tab content
                preloadTab: function(tabId) {
                    // This would integrate with your Livewire component
                    // to prefetch content in the background
                    if (this.config.performance_monitoring) {
                        console.log('Preloading tab:', tabId);
                    }
                },

                // Get current performance statistics
                getStats: function() {
                    const stats = {
                        trackedTabs: Object.keys(this.metrics).length,
                        averageLoadTime: 0,
                        config: this.config
                    };

                    if (stats.trackedTabs > 0) {
                        const loadTimes = Object.values(this.metrics).map(m => m.loadTime);
                        stats.averageLoadTime = Math.round(
                            loadTimes.reduce((a, b) => a + b, 0) / loadTimes.length
                        );
                    }

                    return stats;
                }
            };

            // Initialize performance optimizations when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (window.TabsPerformance) {
                    window.TabsPerformance.setupIntersectionObserver();
                }
            });
        ";
    }

    // Private helper methods

    private function generateTabContent(Tab $tab): string
    {
        if ($tab->hasContent()) {
            return $tab->renderContent();
        }

        if ($tab->hasLivewireComponent()) {
            // For Livewire components, we can't preload the actual content
            // but we can prepare placeholder or metadata
            return $this->generateLivewirePlaceholder($tab);
        }

        return '';
    }

    private function generateLivewirePlaceholder(Tab $tab): string
    {
        return json_encode([
            'type' => 'livewire',
            'component' => $tab->getLivewireComponent(),
            'params' => $tab->getLivewireParams(),
            'preloaded' => true,
        ]);
    }

    private function cleanupOldTrackingData(): void
    {
        // Clean up debounce tracker older than 5 minutes
        $fiveMinutesAgo = (microtime(true) * 1000) - (5 * 60 * 1000);

        $this->debounceTracker = array_filter($this->debounceTracker, fn($timestamp) => $timestamp > $fiveMinutesAgo);

        // Keep only the last 100 load time entries
        if (count($this->loadTimes) > 100) {
            $this->loadTimes = array_slice($this->loadTimes, -100, null, true);
        }
    }

    private function cleanupMemory(): void
    {
        // Clear old tracking data
        $this->debounceTracker = [];
        $this->loadTimes = array_slice($this->loadTimes, -20, null, true);

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    private function logPerformance(string $event, string $tabId, array $context = []): void
    {
        if (config('laravel-tabs.development.performance_monitoring', false)) {
            Log::info("Tabs Performance: {$event}", array_merge([
                'tab_id' => $tabId,
                'event' => $event,
                'timestamp' => now()->toISOString(),
            ], $context));
        }
    }
}
