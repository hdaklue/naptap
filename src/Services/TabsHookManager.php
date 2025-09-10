<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\UI\Tab;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Hook manager for Livewire events and global Laravel events
 * All hooks are handled through proper Livewire event dispatching
 */
class TabsHookManager
{
    private array $config;

    public function __construct()
    {
        $this->config = config('naptab.hooks', []);
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
     * Log hook execution for debugging purposes
     */
    public function logHookExecution(string $hookName, array $context): void
    {
        if ($this->config['debug'] ?? false) {
            Log::debug("NapTab Hook Executed: {$hookName}", $context);
        }
    }
}
