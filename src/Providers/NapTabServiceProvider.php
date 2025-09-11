<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Providers;

use Hdaklue\NapTab\Console\Commands\InstallCommand;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\Services\NapTabConfig;
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
        // Register NapTabConfig as singleton with both string and class bindings
        $this->app->singleton('naptab.config', function ($app) {
            return new NapTabConfig();
        });
        
        $this->app->singleton(NapTabConfig::class, function ($app) {
            return $app['naptab.config'];
        });

        // Register other service classes
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

            // Publish views
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/naptab'),
            ], 'naptab-views');

            // Publish CSS files
            $this->publishes([
                __DIR__ . '/../../resources/css' => public_path('vendor/naptab'),
            ], 'naptab-assets');
        }

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'naptab');
    }

}
