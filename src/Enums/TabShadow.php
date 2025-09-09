<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

enum TabShadow: string
{
    case None = 'shadow-none';
    case Small = 'shadow-sm';
    case Default = 'shadow';
    case Medium = 'shadow-md';
    case Large = 'shadow-lg';
    case ExtraLarge = 'shadow-xl';
    case ExtraLarge2 = 'shadow-2xl';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No Shadow',
            self::Small => 'Small Shadow',
            self::Default => 'Default Shadow',
            self::Medium => 'Medium Shadow',
            self::Large => 'Large Shadow',
            self::ExtraLarge => 'Extra Large Shadow',
            self::ExtraLarge2 => '2XL Shadow',
        };
    }
}
