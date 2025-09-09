<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

enum Shadow: string
{
    case NONE = 'shadow-none';
    case SMALL = 'shadow-sm';
    case DEFAULT = 'shadow';
    case MEDIUM = 'shadow-md';
    case LARGE = 'shadow-lg';
    case EXTRA_LARGE = 'shadow-xl';
    case EXTRA_LARGE_2 = 'shadow-2xl';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No Shadow',
            self::SMALL => 'Small Shadow',
            self::DEFAULT => 'Default Shadow',
            self::MEDIUM => 'Medium Shadow',
            self::LARGE => 'Large Shadow',
            self::EXTRA_LARGE => 'Extra Large Shadow',
            self::EXTRA_LARGE_2 => '2XL Shadow',
        };
    }
}
