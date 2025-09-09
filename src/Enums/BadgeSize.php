<?php

declare(strict_types=1);

namespace Hdaklu\NapTab\Enums;

enum BadgeSize: string
{
    case SMALL = 'px-1.5 py-0.5 text-xs';
    case MEDIUM = 'px-2 py-0.5 text-xs';
    case LARGE = 'px-2.5 py-1 text-sm';

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Small Badge',
            self::MEDIUM => 'Medium Badge',
            self::LARGE => 'Large Badge',
        };
    }
}