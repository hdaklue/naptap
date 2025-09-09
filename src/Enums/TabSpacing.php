<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

enum TabSpacing: string
{
    case SMALL = 'small';
    case NORMAL = 'normal';
    case LARGE = 'large';

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Small Spacing',
            self::NORMAL => 'Normal Spacing',
            self::LARGE => 'Large Spacing',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SMALL => 'Compact spacing for dense layouts',
            self::NORMAL => 'Standard spacing for balanced appearance',
            self::LARGE => 'Generous spacing for spacious layouts',
        };
    }

    // Tab spacing classes
    public function tabGap(): string
    {
        return match ($this) {
            self::SMALL => 'gap-1', // 0.25rem = 4px
            self::NORMAL => 'gap-2', // 0.5rem = 8px
            self::LARGE => 'gap-4', // 1rem = 16px
        };
    }

    // Inner tab content spacing
    public function innerGap(): string
    {
        return match ($this) {
            self::SMALL => 'gap-1', // 0.25rem = 4px
            self::NORMAL => 'gap-2', // 0.5rem = 8px
            self::LARGE => 'gap-3', // 0.75rem = 12px
        };
    }

    // Tab padding
    public function tabPadding(): string
    {
        return match ($this) {
            self::SMALL => 'py-2 px-3', // 0.5rem 0.75rem = 8px 12px
            self::NORMAL => 'py-3 px-4', // 0.75rem 1rem = 12px 16px
            self::LARGE => 'py-4 px-6', // 1rem 1.5rem = 16px 24px
        };
    }

    // Content area margin
    public function contentMargin(): string
    {
        return match ($this) {
            self::SMALL => 'mt-4', // 1rem = 16px
            self::NORMAL => 'mt-6', // 1.5rem = 24px
            self::LARGE => 'mt-8', // 2rem = 32px
        };
    }

    // Navigation container padding
    public function navPadding(): string
    {
        return match ($this) {
            self::SMALL => 'px-0.5', // 0.125rem = 2px
            self::NORMAL => 'px-1', // 0.25rem = 4px
            self::LARGE => 'px-2', // 0.5rem = 8px
        };
    }
}
