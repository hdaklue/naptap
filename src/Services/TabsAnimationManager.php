<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

/**
 * Animation manager for tab transitions with customizable effects
 */
class TabsAnimationManager
{
    private array $config;

    public function __construct()
    {
        $this->config = config('laravel-tabs.features.animations', []);
    }

    /**
     * Get transition classes for tab content
     */
    public function getContentTransitionClasses(bool $active = true): string
    {
        if (!($this->config['enabled'] ?? true)) {
            return '';
        }

        $type = $this->config['type'] ?? 'fade';
        $duration = $this->config['duration'] ?? 200;
        $easing = $this->config['easing'] ?? 'ease-in-out';

        $classes = [
            'transition-all',
            "duration-{$duration}",
            $easing,
        ];

        switch ($type) {
            case 'fade':
                $classes[] = $active ? 'opacity-100' : 'opacity-0';
                break;
            case 'slide':
                $classes[] = 'transform';
                $classes[] = $active ? 'translate-x-0' : 'translate-x-4';
                $classes[] = $active ? 'opacity-100' : 'opacity-70';
                break;
            case 'scale':
                $classes[] = 'transform';
                $classes[] = $active ? 'scale-100' : 'scale-95';
                $classes[] = $active ? 'opacity-100' : 'opacity-0';
                break;
            default:
                $classes[] = $active ? 'opacity-100' : 'opacity-0';
        }

        return implode(' ', $classes);
    }

    /**
     * Get animation classes for loading states
     */
    public function getLoadingAnimationClasses(): string
    {
        if (!($this->config['enabled'] ?? true)) {
            return 'opacity-0 pointer-events-none wire:loading:opacity-100 wire:loading:pointer-events-auto';
        }

        $duration = $this->config['duration'] ?? 200;

        return "opacity-0 pointer-events-none transition-all duration-{$duration} ease-in-out wire:loading:opacity-100 wire:loading:pointer-events-auto";
    }

    /**
     * Get hover animation classes for tab buttons
     */
    public function getTabHoverClasses(): string
    {
        if (!($this->config['enabled'] ?? true)) {
            return 'transition-colors duration-200';
        }

        $duration = $this->config['duration'] ?? 200;
        $easing = $this->config['easing'] ?? 'ease-in-out';

        return "transition-colors duration-{$duration} {$easing} hover:scale-105 active:scale-95";
    }

    /**
     * Generate CSS keyframes for custom animations
     */
    public function generateAnimationCSS(): string
    {
        if (!($this->config['enabled'] ?? true)) {
            return '';
        }

        $duration = $this->config['duration'] ?? 200;
        $easing = $this->config['easing'] ?? 'ease-in-out';

        return "
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeOutDown {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(10px);
                }
            }

            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes slideOutLeft {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(-20px);
                }
            }

            @keyframes scaleIn {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            .animate-fadeInUp {
                animation: fadeInUp {$duration}ms {$easing};
            }

            .animate-fadeOutDown {
                animation: fadeOutDown {$duration}ms {$easing};
            }

            .animate-slideInRight {
                animation: slideInRight {$duration}ms {$easing};
            }

            .animate-slideOutLeft {
                animation: slideOutLeft {$duration}ms {$easing};
            }

            .animate-scaleIn {
                animation: scaleIn {$duration}ms {$easing};
            }

            /* Respect reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .animate-fadeInUp,
                .animate-fadeOutDown,
                .animate-slideInRight,
                .animate-slideOutLeft,
                .animate-scaleIn {
                    animation: none !important;
                }

                [class*=\"transition-\"],
                [class*=\"duration-\"] {
                    transition-duration: 0.01ms !important;
                }
            }
        ";
    }

    /**
     * Get JavaScript for advanced animations
     */
    public function generateAnimationJavaScript(): string
    {
        if (!($this->config['enabled'] ?? true)) {
            return '';
        }

        $type = $this->config['type'] ?? 'fade';
        $duration = $this->config['duration'] ?? 200;

        return '
            window.TabsAnimations = {
                config: ' . json_encode($this->config) . ",

                // Animate tab content change
                animateTabChange: function(fromTab, toTab) {
                    if (!this.config.enabled) return;

                    const type = this.config.type || 'fade';
                    const duration = this.config.duration || 200;

                    if (fromTab) {
                        this.animateOut(fromTab, type, duration);
                    }

                    if (toTab) {
                        setTimeout(() => {
                            this.animateIn(toTab, type, duration);
                        }, duration / 2);
                    }
                },

                // Animate element in
                animateIn: function(element, type, duration) {
                    element.style.display = 'block';

                    switch (type) {
                        case 'fade':
                            element.style.opacity = '0';
                            element.style.transition = 'opacity ' + duration + 'ms ease-in-out';
                            setTimeout(() => element.style.opacity = '1', 10);
                            break;
                        case 'slide':
                            element.style.transform = 'translateX(20px)';
                            element.style.opacity = '0.7';
                            element.style.transition = 'all ' + duration + 'ms ease-in-out';
                            setTimeout(() => {
                                element.style.transform = 'translateX(0)';
                                element.style.opacity = '1';
                            }, 10);
                            break;
                        case 'scale':
                            element.style.transform = 'scale(0.95)';
                            element.style.opacity = '0';
                            element.style.transition = 'all ' + duration + 'ms ease-in-out';
                            setTimeout(() => {
                                element.style.transform = 'scale(1)';
                                element.style.opacity = '1';
                            }, 10);
                            break;
                    }
                },

                // Animate element out
                animateOut: function(element, type, duration) {
                    switch (type) {
                        case 'fade':
                            element.style.transition = 'opacity ' + duration + 'ms ease-in-out';
                            element.style.opacity = '0';
                            break;
                        case 'slide':
                            element.style.transition = 'all ' + duration + 'ms ease-in-out';
                            element.style.transform = 'translateX(-20px)';
                            element.style.opacity = '0.7';
                            break;
                        case 'scale':
                            element.style.transition = 'all ' + duration + 'ms ease-in-out';
                            element.style.transform = 'scale(0.95)';
                            element.style.opacity = '0';
                            break;
                    }

                    setTimeout(() => {
                        element.style.display = 'none';
                    }, duration);
                },

                // Check for reduced motion preference
                respectsReducedMotion: function() {
                    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                },

                // Initialize animations
                init: function() {
                    if (this.respectsReducedMotion()) {
                        this.config.enabled = false;
                        this.config.duration = 0;
                    }

                    // Listen for tab changes
                    document.addEventListener('tabs:switch', (e) => {
                        if (this.config.enabled) {
                            this.triggerTabSwitchAnimation(e.detail);
                        }
                    });
                },

                // Trigger tab switch animation
                triggerTabSwitchAnimation: function(detail) {
                    const activePanel = document.querySelector('[role=\"tabpanel\"][aria-hidden=\"false\"]');
                    if (activePanel && this.config.type !== 'fade') {
                        activePanel.classList.remove('animate-fadeInUp');
                        activePanel.classList.add('animate-' + this.getAnimationClass());
                    }
                },

                // Get animation class based on type
                getAnimationClass: function() {
                    switch (this.config.type) {
                        case 'slide': return 'slideInRight';
                        case 'scale': return 'scaleIn';
                        default: return 'fadeInUp';
                    }
                }
            };

            // Initialize when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (window.TabsAnimations) {
                    window.TabsAnimations.init();
                }
            });
        ";
    }

    /**
     * Get animation configuration for frontend
     */
    public function getAnimationConfig(): array
    {
        return [
            'enabled' => $this->config['enabled'] ?? true,
            'duration' => $this->config['duration'] ?? 200,
            'easing' => $this->config['easing'] ?? 'ease-in-out',
            'type' => $this->config['type'] ?? 'fade',
        ];
    }
}
