<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Providers;

use Hdaklue\NapTab\Console\Commands\InstallCommand;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\Services\TabsAccessibilityManager;
use Hdaklue\NapTab\Services\TabsHookManager;
use Hdaklue\NapTab\Services\TabsLayoutManager;
use Hdaklue\NapTab\Services\TabsNavigationManager;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class NapTabServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register service classes (config is handled by app's NapTabServiceProvider)
        $this->app->singleton(TabsNavigationManager::class);
        $this->app->singleton(TabsHookManager::class);
        $this->app->singleton(TabsLayoutManager::class);
        $this->app->singleton(TabsAccessibilityManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Livewire component
        Livewire::component('nap-tab', NapTab::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/naptab'),
        ], 'naptab-views');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'naptab');
    }

}
