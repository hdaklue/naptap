<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\Enums\BadgeSize;
use Hdaklue\NapTab\Enums\ContentAnimation;
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
    protected BadgeSize $badgeSize = BadgeSize::Medium;
    protected bool $shadowsEnabled = false;
    protected Shadow $shadow = Shadow::Large;
    protected string $shadowColor = 'shadow-blue-500/20 dark:shadow-blue-400/30';
    protected Shadow $hoverShadow = Shadow::Medium;
    protected string $hoverShadowColor = 'shadow-gray-500/10 dark:shadow-gray-400/20';
    protected Shadow $focusShadow = Shadow::Large;
    protected string $focusShadowColor = 'shadow-zinc-500/25';
    protected bool $doubleBorder = true;
    protected TabBorderWidth $borderWidth = TabBorderWidth::Thick;
    protected TabTransition $transitionDuration = TabTransition::Duration300;
    protected TabTransitionTiming $transitionTiming = TabTransitionTiming::EaseInOut;
    protected TabSpacing $spacing = TabSpacing::Normal;
    protected ContentAnimation $contentAnimation = ContentAnimation::Fade;
    protected bool $mobileModalNavigation = false;
    protected bool $containerBottomBorder = true;
    protected ?TabStyle $currentStyle = null;

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

    public function color(TabColor $primary, ?TabColor $secondary = null): self
    {
        $this->primaryColor = $primary;
        if ($secondary) {
            $this->secondaryColor = $secondary;
        }
        return $this;
    }

    public function shadow(Shadow $shadow, ?string $color = null): self
    {
        $this->shadowsEnabled = $shadow !== Shadow::None;
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

    public function transition(TabTransition $duration, ?TabTransitionTiming $timing = null): self
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

    public function border(TabBorderWidth $width, ?bool $doubleBorder = null): self
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
        return $this->shadow(Shadow::None) // No shadows - clean look
            ->border(TabBorderWidth::Thin, false) // Simple border only
            ->transition(TabTransition::Duration200)
            ->spacing(TabSpacing::Small)
            ->radius(TabBorderRadius::Medium) // Rounded tabs
            ->badgeSize(BadgeSize::Small); // Small badges for minimal look
    }

    public function modern(): self
    {
        return $this->shadow(Shadow::Large) // Rich shadows
            ->border(TabBorderWidth::Thick, true) // Double border
            ->transition(TabTransition::Duration300)
            ->spacing(TabSpacing::Normal)
            ->radius(TabBorderRadius::Large) // Rounded corners
            ->badgeSize(BadgeSize::Large); // Large badges for rich look
    }

    public function sharp(): self
    {
        return $this->shadow(Shadow::None) // No shadows
            ->border(TabBorderWidth::None, false) // No borders at all
            ->transition(TabTransition::Duration100)
            ->spacing(TabSpacing::Normal)
            ->radius(TabBorderRadius::None) // Sharp edges - no rounding
            ->badgeSize(BadgeSize::Medium); // Medium badges for sharp look
    }

    public function pills(): self
    {
        return $this->shadow(Shadow::None) // No shadows for clean look
            ->border(TabBorderWidth::FullThin, false) // Full thin border around each pill
            ->transition(TabTransition::Duration200)
            ->spacing(TabSpacing::Small)
            ->radius(TabBorderRadius::Full) // Full radius for pill shape
            ->badgeSize(BadgeSize::Small) // Small badges for clean pill look
            ->containerBorder(false); // No container bottom border for clean pill design
    }

    /**
     * Convert to array for template usage
     * 
     * @return array<string, mixed>
     */
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
                    'container_bottom_border' => $this->containerBottomBorder,
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
                'animations' => [
                    'content_animation' => $this->contentAnimation->value,
                ],
                'mobile' => [
                    'modal_navigation' => $this->mobileModalNavigation,
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

    public function contentAnimation(ContentAnimation $animation): self
    {
        $this->contentAnimation = $animation;
        return $this;
    }

    public function navModalOnMobile(bool $useModal = true): self
    {
        $this->mobileModalNavigation = $useModal;
        return $this;
    }


    public function containerBorder(bool $enabled = true): self
    {
        $this->containerBottomBorder = $enabled;
        return $this;
    }

    /**
     * Get configuration value by key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'debug' => config('app.debug', false),
            'mobile_modal' => $this->mobileModalNavigation,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'shadows_enabled' => $this->shadowsEnabled,
            'double_border' => $this->doubleBorder,
            'container_border' => $this->containerBottomBorder,
            default => $default,
        };
    }

    /**
     * Set configuration value by key (for dynamic configuration)
     */
    public function set(string $key, mixed $value): self
    {
        match ($key) {
            'debug' => null, // Read-only, handled by Laravel config
            'shadows_enabled' => $this->shadowsEnabled = (bool) $value,
            'double_border' => $this->doubleBorder = (bool) $value,
            'mobile_modal' => $this->mobileModalNavigation = (bool) $value,
            'container_border' => $this->containerBottomBorder = (bool) $value,
            default => null, // Ignore unknown keys
        };

        return $this;
    }
}
