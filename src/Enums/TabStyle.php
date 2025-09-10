<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

use Hdaklue\NapTab\Services\NapTabConfig;

enum TabStyle: string
{
    case Minimal = 'minimal';
    case Modern = 'modern';
    case Sharp = 'sharp';
    case Pills = 'pills';

    public function label(): string
    {
        return match ($this) {
            self::Minimal => 'Minimal',
            self::Modern => 'Modern',
            self::Sharp => 'Sharp',
            self::Pills => 'Pills',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Minimal => 'Clean design with no shadows and minimal borders',
            self::Modern => 'Balanced design with shadows and smooth transitions',
            self::Sharp => 'Bold design with sharp edges and no radius',
            self::Pills => 'Rounded pill-shaped tabs with full border radius and subtle styling',
        };
    }

    public function configure(NapTabConfig $config): NapTabConfig
    {
        return match ($this) {
            self::Minimal => $config->minimal(),
            self::Modern => $config->modern(),
            self::Sharp => $config->sharp(),
            self::Pills => $config->pills(),
        };
    }
}
