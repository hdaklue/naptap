<?php

declare(strict_types=1);

namespace Hdaklu\NapTab\Enums;

enum TabTransitionTiming: string
{
    case Linear = 'linear';
    case EaseIn = 'ease-in';
    case EaseOut = 'ease-out';
    case EaseInOut = 'ease-in-out';

    public function label(): string
    {
        return match ($this) {
            self::Linear => 'Linear',
            self::EaseIn => 'Ease In',
            self::EaseOut => 'Ease Out',
            self::EaseInOut => 'Ease In Out',
        };
    }
}