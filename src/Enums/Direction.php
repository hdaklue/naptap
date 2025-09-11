<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Enums;

/**
 * Tab layout direction for desktop layouts
 */
enum Direction: string
{
    case Horizontal = 'horizontal';
    case Aside = 'aside';

    /**
     * Get Tailwind CSS classes for tab container direction
     */
    public function containerClasses(): string
    {
        return match ($this) {
            self::Horizontal => 'flex-col',
            self::Aside => 'flex-col',
        };
    }

    /**
     * Get Tailwind CSS classes for tab navigation direction
     */
    public function navigationClasses(): string
    {
        return match ($this) {
            self::Horizontal => 'flex-row overflow-x-auto',
            self::Aside => 'flex-col overflow-y-auto min-w-48 mr-6',
        };
    }

    /**
     * Get Tailwind CSS classes for content area
     */
    public function contentClasses(): string
    {
        return match ($this) {
            self::Horizontal => 'w-full',
            self::Aside => 'flex-1 min-w-0 pl-2',
        };
    }

    /**
     * Get responsive classes that only apply on desktop
     */
    public function responsiveClasses(): string
    {
        return match ($this) {
            self::Horizontal => '', // Default flex-col behavior, no responsive override needed
            self::Aside => 'md:flex-row',   // Stack on mobile, aside on tablet and up
        };
    }
}