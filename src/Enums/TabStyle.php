<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

use Hdaklue\NapTab\Services\NapTabConfig;

enum TabStyle: string
{
    case MINIMAL = 'minimal';
    case MODERN = 'modern';
    case SHARP = 'sharp';

    public function label(): string
    {
        return match ($this) {
            self::MINIMAL => 'Minimal',
            self::MODERN => 'Modern',
            self::SHARP => 'Sharp',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MINIMAL => 'Clean design with no shadows and minimal borders',
            self::MODERN => 'Balanced design with shadows and smooth transitions',
            self::SHARP => 'Bold design with sharp edges and no radius',
        };
    }

    public function configure(NapTabConfig $config): NapTabConfig
    {
        return match ($this) {
            self::MINIMAL => $config->minimal(),
            self::MODERN => $config->modern(),
            self::SHARP => $config->sharp(),
        };
    }
}
