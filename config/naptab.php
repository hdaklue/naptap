<?php

use Hdaklu\NapTab\Enums\Shadow;
use Hdaklu\NapTab\Enums\TabBorderRadius;
use Hdaklu\NapTab\Enums\TabColor;
use Hdaklu\NapTab\Enums\TabSpacing;
use Hdaklu\NapTab\Enums\TabStyle;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    |
    | These are the default settings for NapTab. You can override these
    | by calling methods on the NapTabConfig instance in your service provider.
    |
    */

    'default' => [
        'style' => TabStyle::MODERN,
        'primary_color' => TabColor::Blue,
        'secondary_color' => TabColor::Gray,
        'border_radius' => TabBorderRadius::Medium,
        'badge_radius' => TabBorderRadius::Full,
        'spacing' => TabSpacing::NORMAL,
        'shadow' => Shadow::NONE,
        'shadows_enabled' => false,
        'double_border' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | View Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which views to use for the tab components.
    |
    */

    'views' => [
        'tab-container' => 'naptab::components.tabs-container.index',
        'tab-content' => 'naptab::components.tabs-container.tab-content',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for tab content and configuration.
    |
    */

    'cache' => [
        'enabled' => env('NAPTAB_CACHE_ENABLED', false),
        'ttl' => env('NAPTAB_CACHE_TTL', 3600), // 1 hour
        'key_prefix' => env('NAPTAB_CACHE_PREFIX', 'naptab'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings.
    |
    */

    'performance' => [
        'debounce_ms' => env('NAPTAB_DEBOUNCE_MS', 150),
        'preload_adjacent' => env('NAPTAB_PRELOAD_ADJACENT', true),
        'lazy_load' => env('NAPTAB_LAZY_LOAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security-related settings.
    |
    */

    'security' => [
        'enable_authorization' => env('NAPTAB_AUTHORIZATION', true),
        'enable_visibility_checks' => env('NAPTAB_VISIBILITY_CHECKS', true),
    ],
];