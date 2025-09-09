<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\Enums\BadgeSize;
use Hdaklue\NapTab\Enums\Shadow;
use Hdaklue\NapTab\Enums\TabBorderRadius;
use Hdaklue\NapTab\Enums\TabBorderWidth;
use Hdaklue\NapTab\Enums\TabColor;
use Hdaklue\NapTab\Enums\TabSpacing;
use Hdaklue\NapTab\Enums\TabStyle;
use Hdaklue\NapTab\Enums\TabTransition;
use Hdaklue\NapTab\Enums\TabTransitionTiming;

/**
 * NapTab configuration service with fluent API
 * Registered in container for global access
 */
class NapTabConfig
{
    protected TabColor $primaryColor = TabColor::Blue;
    protected TabColor $secondaryColor = TabColor::Gray;
    protected TabBorderRadius $borderRadius = TabBorderRadius::Medium;
    protected TabBorderRadius $badgeRadius = TabBorderRadius::Full;
    protected BadgeSize $badgeSize = BadgeSize::MEDIUM;
    protected bool $shadowsEnabled = false;
    protected Shadow $shadow = Shadow::LARGE;
    protected string $shadowColor = 'shadow-blue-500/20 dark:shadow-blue-400/30';
    protected Shadow $hoverShadow = Shadow::MEDIUM;
    protected string $hoverShadowColor = 'shadow-gray-500/10 dark:shadow-gray-400/20';
    protected Shadow $focusShadow = Shadow::LARGE;
    protected string $focusShadowColor = 'shadow-blue-500/25';
    protected bool $doubleBorder = true;
    protected TabBorderWidth $borderWidth = TabBorderWidth::Thick;
    protected TabTransition $transitionDuration = TabTransition::Duration300;
    protected TabTransitionTiming $transitionTiming = TabTransitionTiming::EaseInOut;
    protected TabSpacing $spacing = TabSpacing::NORMAL;
    protected null|TabStyle $currentStyle = null;

    public static function create(): self
    {
        return new self();
    }

    // Simplified API
    public function style(TabStyle $style): self
    {
        $this->currentStyle = $style;
        return $style->configure($this);
    }

    public function color(TabColor $primary, TabColor $secondary = null): self
    {
        $this->primaryColor = $primary;
        if ($secondary) {
            $this->secondaryColor = $secondary;
        }
        return $this;
    }

    public function shadow(Shadow $shadow, string $color = null): self
    {
        $this->shadowsEnabled = $shadow !== Shadow::NONE;
        $this->shadow = $shadow;
        if ($color) {
            $this->shadowColor = $color;
        }
        return $this;
    }

    public function radius(TabBorderRadius $radius): self
    {
        $this->borderRadius = $radius;
        return $this;
    }

    public function transition(TabTransition $duration, TabTransitionTiming $timing = null): self
    {
        $this->transitionDuration = $duration;
        if ($timing) {
            $this->transitionTiming = $timing;
        }
        return $this;
    }

    public function spacing(TabSpacing $spacing): self
    {
        $this->spacing = $spacing;
        return $this;
    }

    public function border(TabBorderWidth $width, bool $doubleBorder = null): self
    {
        $this->borderWidth = $width;
        if ($doubleBorder !== null) {
            $this->doubleBorder = $doubleBorder;
        }
        return $this;
    }

    // Preset methods (for internal use by TabStyle enum)
    public function minimal(): self
    {
        return $this->shadow(Shadow::NONE) // No shadows - clean look
            ->border(TabBorderWidth::Thin, false) // Simple border only
            ->transition(TabTransition::Duration200)
            ->spacing(TabSpacing::SMALL)
            ->radius(TabBorderRadius::Medium) // Rounded tabs
            ->badgeSize(BadgeSize::SMALL); // Small badges for minimal look
    }

    public function modern(): self
    {
        return $this->shadow(Shadow::LARGE) // Rich shadows
            ->border(TabBorderWidth::Thick, true) // Double border
            ->transition(TabTransition::Duration300)
            ->spacing(TabSpacing::NORMAL)
            ->radius(TabBorderRadius::Large) // Rounded corners
            ->badgeSize(BadgeSize::LARGE); // Large badges for rich look
    }

    public function sharp(): self
    {
        return $this->shadow(Shadow::NONE) // No shadows
            ->border(TabBorderWidth::None, false) // No borders at all
            ->transition(TabTransition::Duration100)
            ->spacing(TabSpacing::NORMAL)
            ->radius(TabBorderRadius::None) // Sharp edges - no rounding
            ->badgeSize(BadgeSize::MEDIUM); // Medium badges for sharp look
    }

    // Convert to array for template usage
    public function toArray(): array
    {
        return [
            'colors' => [
                'primary' => $this->primaryColor->value,
                'secondary' => $this->secondaryColor->value,
            ],
            'styles' => [
                'border_radius' => $this->borderRadius->value,
                'badge_radius' => $this->badgeRadius->value,
                'badge_size' => $this->badgeSize->value,
                'current_style' => $this->currentStyle?->value,
                'shadows' => [
                    'enabled' => $this->shadowsEnabled,
                    'tab_shadow' => $this->shadow->value,
                    'tab_shadow_color' => $this->shadowColor,
                    'hover_shadow' => $this->hoverShadow->value,
                    'hover_shadow_color' => $this->hoverShadowColor,
                    'focus_shadow' => $this->focusShadow->value,
                    'focus_shadow_color' => $this->focusShadowColor,
                ],
                'borders' => [
                    'double_border' => $this->doubleBorder,
                    'border_width' => $this->borderWidth->value,
                ],
                'transitions' => [
                    'duration' => $this->transitionDuration->value,
                    'timing' => $this->transitionTiming->value,
                ],
                'spacing' => [
                    'tab_gap' => $this->spacing->tabGap(),
                    'inner_gap' => $this->spacing->innerGap(),
                    'tab_padding' => $this->spacing->tabPadding(),
                    'content_margin' => $this->spacing->contentMargin(),
                    'nav_padding' => $this->spacing->navPadding(),
                ],
            ],
        ];
    }

    // Getters
    public function getPrimaryColor(): TabColor
    {
        return $this->primaryColor;
    }

    public function getSecondaryColor(): TabColor
    {
        return $this->secondaryColor;
    }

    public function getBorderRadius(): TabBorderRadius
    {
        return $this->borderRadius;
    }

    public function isShadowsEnabled(): bool
    {
        return $this->shadowsEnabled;
    }

    public function isDoubleBorderEnabled(): bool
    {
        return $this->doubleBorder;
    }

    public function getSpacing(): TabSpacing
    {
        return $this->spacing;
    }

    public function badgeRadius(TabBorderRadius $radius): self
    {
        $this->badgeRadius = $radius;
        return $this;
    }

    public function badgeSize(BadgeSize $size): self
    {
        $this->badgeSize = $size;
        return $this;
    }
}
