<?php

declare(strict_types=1);

namespace Hdaklu\NapTab;

use Hdaklu\NapTab\Enums\BadgeSize;
use Hdaklu\NapTab\Enums\Shadow;
use Hdaklu\NapTab\Enums\TabBorderRadius;
use Hdaklu\NapTab\Enums\TabColor;
use Hdaklu\NapTab\Enums\TabSpacing;
use Hdaklu\NapTab\Enums\TabStyle;
use Hdaklu\NapTab\Livewire\NapTab;
use Hdaklu\NapTab\Services\NapTabConfig;
use Hdaklu\NapTab\Services\TabsAccessibilityManager;
use Hdaklu\NapTab\Services\TabsHookManager;
use Hdaklu\NapTab\Services\TabsLayoutManager;
use Hdaklu\NapTab\Services\TabsNavigationManager;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class NapTabServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register NapTabConfig as a singleton in the container
        $this->app->singleton('naptab.config', function () {
            return $this->createNapTabConfig();
        });

        // Register aliases for easy access
        $this->app->alias('naptab.config', NapTabConfig::class);
        $this->app->alias('naptab.config', 'NapTabConfig');

        // Register service classes
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

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/naptab'),
        ], 'naptab-views');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/naptab.php' => config_path('naptab.php'),
        ], 'naptab-config');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'naptab');

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/naptab.php', 'naptab');

        // Make NapTabConfig available to Blade views
        view()->composer('naptab::components.*', function ($view) {
            $view->with('napTabConfig', app('naptab.config'));
        });
    }

    /**
     * Create and configure the NapTabConfig instance
     * This is where you customize your NapTab appearance
     */
    protected function createNapTabConfig(): NapTabConfig
    {
        // Environment-specific configurations with clean API
        return match (app()->environment()) {
            'local', 'development' => NapTabConfig::create()
                ->style(TabStyle::SHARP) // Use MODERN for better shadow demo
                ->radius(TabBorderRadius::Medium)
                ->badgeRadius(TabBorderRadius::None)
                ->color(TabColor::Blue, TabColor::Gray)
                ->shadow(Shadow::NONE), // Shadows controlled by shadow() method
            'production' => NapTabConfig::create()
                ->style(TabStyle::MINIMAL)
                ->radius(TabBorderRadius::Small)
                ->color(TabColor::Gray, TabColor::Slate)
                ->spacing(TabSpacing::NORMAL)
                ->shadow(Shadow::NONE), // Shadows controlled by shadow() method
            default => NapTabConfig::create()
                ->style(TabStyle::MODERN)
                ->radius(TabBorderRadius::Large)
                ->color(TabColor::Red)
                ->spacing(TabSpacing::LARGE)
                ->shadow(Shadow::LARGE), // Shadows controlled by shadow() method
        };
    }
}
