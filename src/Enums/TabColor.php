<?php

declare(strict_types=1);

namespace Hdaklu\NapTab\Enums;

enum TabColor: string
{
    case Blue = 'blue';
    case Red = 'red';
    case Green = 'green';
    case Yellow = 'yellow';
    case Purple = 'purple';
    case Pink = 'pink';
    case Indigo = 'indigo';
    case Gray = 'gray';
    case Emerald = 'emerald';
    case Teal = 'teal';
    case Cyan = 'cyan';
    case Sky = 'sky';
    case Amber = 'amber';
    case Orange = 'orange';
    case Rose = 'rose';
    case Fuchsia = 'fuchsia';
    case Violet = 'violet';
    case Lime = 'lime';
    case Stone = 'stone';
    case Neutral = 'neutral';
    case Zinc = 'zinc';
    case Slate = 'slate';

    public function label(): string
    {
        return match ($this) {
            self::Blue => 'Blue',
            self::Red => 'Red',
            self::Green => 'Green',
            self::Yellow => 'Yellow',
            self::Purple => 'Purple',
            self::Pink => 'Pink',
            self::Indigo => 'Indigo',
            self::Gray => 'Gray',
            self::Emerald => 'Emerald',
            self::Teal => 'Teal',
            self::Cyan => 'Cyan',
            self::Sky => 'Sky',
            self::Amber => 'Amber',
            self::Orange => 'Orange',
            self::Rose => 'Rose',
            self::Fuchsia => 'Fuchsia',
            self::Violet => 'Violet',
            self::Lime => 'Lime',
            self::Stone => 'Stone',
            self::Neutral => 'Neutral',
            self::Zinc => 'Zinc',
            self::Slate => 'Slate',
        };
    }
}