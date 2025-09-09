<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Hdaklue\NapTab\Enums\ContentAnimation;

/**
 * Animation manager for tab transitions with customizable effects
 */
class TabsAnimationManager
{
    private ContentAnimation $animation;
    private int $duration;
    private string $easing;

    public function __construct(
        ContentAnimation $animation = ContentAnimation::Fade,
        int $duration = 200,
        string $easing = 'ease-in-out'
    ) {
        $this->animation = $animation;
        $this->duration = $duration;
        $this->easing = $easing;
    }

    /**
     * Get Alpine.js transition directives
     */
    public function getAlpineTransitionDirectives(): array
    {
        return match ($this->animation) {
            ContentAnimation::None => [],
            ContentAnimation::Fade => [
                'x-transition:enter' => "transition ease-out duration-{$this->duration}",
                'x-transition:enter-start' => 'opacity-0',
                'x-transition:enter-end' => 'opacity-100',
            ],
            ContentAnimation::Scale => [
                'x-transition:enter' => "transition ease-out duration-{$this->duration}",
                'x-transition:enter-start' => 'opacity-0 transform scale-95',
                'x-transition:enter-end' => 'opacity-100 transform scale-100',
            ],
            ContentAnimation::Slide => [
                'x-transition:enter' => "transition ease-out duration-{$this->duration}",
                'x-transition:enter-start' => 'opacity-0 transform translate-x-4',
                'x-transition:enter-end' => 'opacity-100 transform translate-x-0',
            ],
        };
    }

    /**
     * Get CSS classes for content container
     */
    public function getContentClasses(): string
    {
        if ($this->animation === ContentAnimation::None) {
            return 'nap-tab-content w-full';
        }

        return "nap-tab-content w-full transition-opacity duration-{$this->duration} {$this->easing}";
    }

    /**
     * Generate CSS for content animations
     */
    public function generateAnimationCSS(): string
    {
        if ($this->animation === ContentAnimation::None) {
            return '';
        }

        return "
        /* Content transitions */
        .nap-tab-content {
            transition: opacity {$this->duration}ms {$this->easing};
        }

        .nap-tab-content.loading {
            opacity: 0.6;
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            .nap-tab-content,
            [class*=\"transition-\"],
            [class*=\"duration-\"] {
                transition-duration: 0.01ms !important;
                transform: none !important;
            }
        }
        ";
    }

    /**
     * Get animation configuration
     */
    public function getAnimationConfig(): array
    {
        return [
            'type' => $this->animation->value,
            'duration' => $this->duration,
            'easing' => $this->easing,
        ];
    }

    /**
     * Get animation type
     */
    public function getAnimation(): ContentAnimation
    {
        return $this->animation;
    }

    /**
     * Get animation duration in milliseconds
     */
    public function getDuration(): int
    {
        return $this->duration;
    }
}
