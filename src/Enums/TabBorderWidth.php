<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

enum TabBorderWidth: string
{
    case None = 'border-b-0';
    case Thin = 'border-b';
    case Medium = 'border-b-2';
    case Thick = 'border-b-4';
    case ExtraThick = 'border-b-8';
    case FullThin = 'border';
    case FullMedium = 'border-2';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No Border',
            self::Thin => 'Thin Bottom Border (1px)',
            self::Medium => 'Medium Bottom Border (2px)',
            self::Thick => 'Thick Bottom Border (4px)',
            self::ExtraThick => 'Extra Thick Bottom Border (8px)',
            self::FullThin => 'Thin Full Border (1px)',
            self::FullMedium => 'Medium Full Border (2px)',
        };
    }
}
