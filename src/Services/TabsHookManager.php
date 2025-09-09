<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\UI\Tab;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Simplified hook manager for JavaScript integration and global events
 * PHP hooks are handled directly on Tab instances
 */
class TabsHookManager
{
    private array $config;
    private bool $jsHooksEnabled;

    public function __construct()
    {
        $this->config = config('naptab.hooks', []);
        $this->jsHooksEnabled = $this->config['javascript_hooks'] ?? true;
    }

    /**
     * Dispatch Laravel event for tab operations (optional global events)
     */
    public function dispatchEvent(string $eventName, array $context): void
    {
        if ($this->config['dispatch_events'] ?? false) {
            Event::dispatch("tabs.{$eventName}", [$context]);
        }
    }

    /**
     * Get JavaScript hooks configuration for frontend
     */
    public function getJavaScriptHooksConfig(): array
    {
        if (!$this->jsHooksEnabled) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'hooks' => $this->config['available_hooks'] ?? [],
            'analytics' => $this->config['analytics'] ?? [],
            'debug' => config('laravel-tabs.development.debug_mode', false),
        ];
    }

    /**
     * Generate JavaScript hook code for frontend integration
     */
    public function generateJavaScriptHooks(): string
    {
        if (!$this->jsHooksEnabled) {
            return '';
        }

        $config = json_encode($this->getJavaScriptHooksConfig());

        return "
            window.TabsHooks = {
                config: {$config},

                init: function(componentId, tabs, config) {
                    this.executeHook('init', { componentId, tabs, config });
                },

                beforeTabLoad: function(tabId, context) {
                    return this.executeHook('beforeTabLoad', { tabId, context });
                },

                afterTabLoad: function(tabId, content, loadTime, context) {
                    this.executeHook('afterTabLoad', { tabId, content, loadTime, context });
                },

                onTabError: function(tabId, error, context) {
                    this.executeHook('onTabError', { tabId, error, context });
                },

                onTabSwitch: function(fromTabId, toTabId, context) {
                    return this.executeHook('onTabSwitch', { fromTabId, toTabId, context });
                },

                executeHook: function(hookName, data) {
                    const event = new CustomEvent('tabs:' + hookName, { detail: data });
                    document.dispatchEvent(event);

                    if (this.config.debug) {
                        console.log('Tabs Hook:', hookName, data);
                    }

                    return data;
                }
            };
        ";
    }
}
