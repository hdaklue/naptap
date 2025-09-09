<?php

use Hdaklue\NapTab\Enums\Shadow;
use Hdaklue\NapTab\Enums\TabBorderRadius;
use Hdaklue\NapTab\Enums\TabColor;
use Hdaklue\NapTab\Enums\TabSpacing;
use Hdaklue\NapTab\Enums\TabStyle;

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
        'style' => TabStyle::Modern,
        'primary_color' => TabColor::Blue,
        'secondary_color' => TabColor::Gray,
        'border_radius' => TabBorderRadius::Medium,
        'badge_radius' => TabBorderRadius::Full,
        'spacing' => TabSpacing::Normal,
        'shadow' => Shadow::None,
        'shadows_enabled' => false,
        'double_border' => true,
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
