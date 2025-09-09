<?php

declare(strict_types=1);

namespace Hdaklu\NapTab\Enums;

enum TabBorderRadius: string
{
    case None = 'rounded-none';
    case Small = 'rounded-sm';
    case Default = 'rounded';
    case Medium = 'rounded-md';
    case Large = 'rounded-lg';
    case ExtraLarge = 'rounded-xl';
    case ExtraLarge2 = 'rounded-2xl';
    case ExtraLarge3 = 'rounded-3xl';
    case Full = 'rounded-full';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No Radius',
            self::Small => 'Small Radius',
            self::Default => 'Default Radius',
            self::Medium => 'Medium Radius',
            self::Large => 'Large Radius',
            self::ExtraLarge => 'Extra Large Radius',
            self::ExtraLarge2 => '2XL Radius',
            self::ExtraLarge3 => '3XL Radius',
            self::Full => 'Full Radius',
        };
    }
}