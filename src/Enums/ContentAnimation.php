<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

enum ContentAnimation: string
{
    case None = 'none';
    case Fade = 'fade';
    case Scale = 'scale';
    case Slide = 'slide';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No Animation',
            self::Fade => 'Fade',
            self::Scale => 'Scale',
            self::Slide => 'Slide',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::None => 'No transition animation between tabs',
            self::Fade => 'Simple opacity fade transition',
            self::Scale => 'Scale and fade transition effect',
            self::Slide => 'Slide with fade transition effect',
        };
    }
}