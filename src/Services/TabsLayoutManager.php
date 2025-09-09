<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Services;

use Illuminate\Support\Facades\Log;

/**
 * Layout manager for tabs positioning, orientation, and styling
 * Handles horizontal/vertical, positioning, sticky behavior, and theming
 */
class TabsLayoutManager
{
    private array $config;
    private array $themeConfig;

    public function __construct()
    {
        $this->config = config('laravel-tabs.layout', []);
        $this->themeConfig = config('laravel-tabs.theme', []);
    }

    /**
     * Get container classes based on layout configuration
     */
    public function getContainerClasses(): string
    {
        $classes = [];

        // Base container class
        $classes[] = $this->config['container_class'] ?? 'w-full';

        // Position classes
        $position = $this->config['position'] ?? 'top';
        switch ($position) {
            case 'left':
                $classes[] = 'flex flex-row';
                break;
            case 'right':
                $classes[] = 'flex flex-row-reverse';
                break;
            case 'bottom':
                $classes[] = 'flex flex-col-reverse';
                break;
            case 'top':
            default:
                $classes[] = 'flex flex-col';
                break;
        }

        // Sticky behavior
        if ($this->config['sticky'] ?? false) {
            $classes[] = 'sticky top-0 z-10 bg-white';
            $classes[] = $this->themeConfig['style']['shadow'] ?? 'shadow-sm';
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Get navigation classes based on orientation and layout
     */
    public function getNavigationClasses(): string
    {
        $classes = [];
        $orientation = $this->config['orientation'] ?? 'horizontal';
        $position = $this->config['position'] ?? 'top';

        // Base navigation styling
        if ($position === 'top' || $position === 'bottom') {
            $classes[] = 'border-b border-gray-200';
        } elseif ($position === 'left') {
            $classes[] = 'border-r border-gray-200 min-w-[200px]';
        } elseif ($position === 'right') {
            $classes[] = 'border-l border-gray-200 min-w-[200px]';
        }

        // Orientation classes
        if ($orientation === 'vertical' || $position === 'left' || $position === 'right') {
            $classes[] = 'flex flex-col space-y-1 p-2';

            if ($this->config['scrollable'] ?? true) {
                $classes[] = 'overflow-y-auto max-h-[400px]';
            }
        } else {
            // Horizontal layout
            $classes[] = '-mb-px flex space-x-8';

            if ($this->config['scrollable'] ?? true) {
                $classes[] = 'overflow-x-auto';
            }

            // Justify content
            $justify = $this->config['justify'] ?? 'start';
            switch ($justify) {
                case 'center':
                    $classes[] = 'justify-center';
                    break;
                case 'end':
                    $classes[] = 'justify-end';
                    break;
                case 'between':
                    $classes[] = 'justify-between';
                    break;
                case 'around':
                    $classes[] = 'justify-around';
                    break;
                case 'evenly':
                    $classes[] = 'justify-evenly';
                    break;
                case 'start':
                default:
                    $classes[] = 'justify-start';
                    break;
            }
        }

        // Full width tabs
        if ($this->config['full_width'] ?? false) {
            if ($orientation === 'horizontal') {
                $classes[] = 'w-full';
                $classes = array_diff($classes, ['space-x-8']); // Remove spacing for full width
            }
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Get tab button classes with modern premium theming
     */
    public function getTabButtonClasses(bool $active, bool $disabled): string
    {
        $classes = [];
        $colors = $this->themeConfig['colors'] ?? [];
        $style = $this->themeConfig['style'] ?? [];
        $orientation = $this->config['orientation'] ?? 'horizontal';
        $position = $this->config['position'] ?? 'top';
        $primaryColor = $colors['primary'] ?? 'blue';

        // Base classes with enhanced transitions and focus
        $classes[] = 'font-medium text-sm transition-all duration-300 ease-in-out focus:outline-none group';
        $classes[] = 'focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900';
        $classes[] =
            'focus:ring-' . $primaryColor . '-500/50 focus:shadow-lg focus:shadow-' . $primaryColor . '-500/25';

        // Border radius
        $classes[] = $style['radius'] ?? 'rounded-md';

        // Orientation and position specific classes
        if ($orientation === 'vertical' || $position === 'left' || $position === 'right') {
            // Vertical tab styling with premium enhancements
            $classes[] = 'w-full text-left px-4 py-3 mb-1 relative overflow-hidden';

            if ($active) {
                // Double border effect for vertical tabs
                $classes[] = "bg-{$primaryColor}-50 dark:bg-{$primaryColor}-900/20 border border-{$primaryColor}-200 dark:border-{$primaryColor}-700";
                $classes[] = "text-{$primaryColor}-700 dark:text-{$primaryColor}-300 border-l-4 border-l-{$primaryColor}-500 dark:border-l-{$primaryColor}-400";
                $classes[] = "shadow-md shadow-{$primaryColor}-500/10 dark:shadow-{$primaryColor}-400/20";
                $classes[] = "before:absolute before:inset-0 before:bg-gradient-to-r before:from-{$primaryColor}-500/5 before:to-transparent";
            } else {
                $classes[] = 'text-gray-600 dark:text-gray-300 border border-transparent hover:border-gray-200 dark:hover:border-gray-700';
                $classes[] = 'hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/50';
                $classes[] = 'hover:shadow-sm hover:shadow-gray-500/10 dark:hover:shadow-gray-400/20';
                $classes[] = 'border-l-4 border-l-transparent hover:border-l-gray-300 dark:hover:border-l-gray-600';
            }
        } else {
            // Enhanced horizontal tab styling
            $classes[] = 'whitespace-nowrap py-3 px-4 relative overflow-hidden';

            if ($this->config['full_width'] ?? false) {
                $classes[] = 'flex-1 text-center';
            }

            if ($active) {
                // Double border effect - outer border + inner accent
                $classes[] = "border-b-4 border-{$primaryColor}-500 dark:border-{$primaryColor}-400";
                $classes[] = "text-{$primaryColor}-700 dark:text-{$primaryColor}-300 font-semibold";
                $classes[] = "bg-gradient-to-t from-{$primaryColor}-50/80 to-transparent dark:from-{$primaryColor}-900/30";
                $classes[] = "shadow-lg shadow-{$primaryColor}-500/20 dark:shadow-{$primaryColor}-400/30";
                // Double border inner effect
                $classes[] = 'after:absolute after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2';
                $classes[] = 'after:w-[calc(100%-16px)] after:h-0.5 after:bg-white dark:after:bg-gray-900';
                $classes[] = 'after:shadow-[0_0_0_1px_rgb(59_130_246/0.5)] dark:after:shadow-[0_0_0_1px_rgb(96_165_250/0.5)]';
            } else {
                $classes[] = 'border-b-4 border-transparent text-gray-600 dark:text-gray-400';
                $classes[] = 'hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-600';
                $classes[] = 'hover:bg-gradient-to-t hover:from-gray-50/80 hover:to-transparent';
                $classes[] = 'dark:hover:from-gray-800/50 dark:hover:to-transparent';
                $classes[] = 'hover:shadow-md hover:shadow-gray-500/10 dark:hover:shadow-gray-400/20';
            }
        }

        // Enhanced disabled state
        if ($disabled) {
            $disabledColor = $colors['disabled'] ?? 'gray';
            $classes[] = "opacity-40 cursor-not-allowed text-{$disabledColor}-400 dark:text-{$disabledColor}-500";
            $classes[] = 'pointer-events-none grayscale';
        } else {
            $classes[] = 'cursor-pointer';
            // Add subtle scale effect on hover
            $classes[] = 'hover:scale-[1.02] active:scale-[0.98] transform-gpu';
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Get content area classes
     */
    public function getContentClasses(): string
    {
        $classes = [];
        $position = $this->config['position'] ?? 'top';
        $orientation = $this->config['orientation'] ?? 'horizontal';

        // Base content class
        $classes[] = $this->config['content_class'] ?? 'mt-6';

        // Position-specific adjustments
        if ($position === 'left' || $position === 'right') {
            $classes[] = 'flex-1 p-4';
            $classes = array_diff($classes, ['mt-6']); // Remove top margin for side layouts
        } elseif ($position === 'bottom') {
            $classes = array_diff($classes, ['mt-6']); // Remove top margin
            $classes[] = 'mb-6';
        }

        // Minimum height for better UX
        $classes[] = 'relative min-h-[400px] overflow-hidden';

        return implode(' ', array_unique($classes));
    }

    /**
     * Get enhanced badge classes with theming and config-based radius
     */
    public function getBadgeClasses(bool $active, string $badgeType = 'default'): string
    {
        $classes = [];
        $colors = $this->themeConfig['colors'] ?? [];
        $style = $this->themeConfig['style'] ?? [];

        // Base classes with enhanced styling
        $classes[] = 'inline-flex items-center px-2.5 py-1 text-xs font-semibold';
        $classes[] = 'transition-all duration-200 ease-in-out';
        $classes[] = 'shadow-sm ring-1 ring-inset';

        // Use border radius from config
        $borderRadius = $style['radius'] ?? 'rounded-md';
        // Convert to badge-appropriate radius (smaller badges look better with more rounded corners)
        $badgeRadius = match ($borderRadius) {
            'rounded-none' => 'rounded-none',
            'rounded-sm' => 'rounded',
            'rounded' => 'rounded-md',
            'rounded-md' => 'rounded-lg',
            'rounded-lg' => 'rounded-xl',
            'rounded-xl' => 'rounded-2xl',
            'rounded-2xl' => 'rounded-3xl',
            'rounded-3xl' => 'rounded-full',
            'rounded-full' => 'rounded-full',
            default => 'rounded-full',
        };
        $classes[] = $badgeRadius;

        // Enhanced badge colors with better contrast and dark mode support
        switch ($badgeType) {
            case 'success':
                $color = $colors['success'] ?? 'emerald';
                if ($active) {
                    $classes[] = "bg-{$color}-100 text-{$color}-800 ring-{$color}-200";
                    $classes[] = "dark:bg-{$color}-900/30 dark:text-{$color}-300 dark:ring-{$color}-700/50";
                } else {
                    $classes[] = "bg-{$color}-50 text-{$color}-700 ring-{$color}-100";
                    $classes[] = "dark:bg-{$color}-900/20 dark:text-{$color}-400 dark:ring-{$color}-800/30";
                }
                break;
            case 'warning':
                $color = $colors['warning'] ?? 'amber';
                if ($active) {
                    $classes[] = "bg-{$color}-100 text-{$color}-800 ring-{$color}-200";
                    $classes[] = "dark:bg-{$color}-900/30 dark:text-{$color}-300 dark:ring-{$color}-700/50";
                } else {
                    $classes[] = "bg-{$color}-50 text-{$color}-700 ring-{$color}-100";
                    $classes[] = "dark:bg-{$color}-900/20 dark:text-{$color}-400 dark:ring-{$color}-800/30";
                }
                break;
            case 'danger':
            case 'error':
                $color = $colors['danger'] ?? 'red';
                if ($active) {
                    $classes[] = "bg-{$color}-100 text-{$color}-800 ring-{$color}-200";
                    $classes[] = "dark:bg-{$color}-900/30 dark:text-{$color}-300 dark:ring-{$color}-700/50";
                } else {
                    $classes[] = "bg-{$color}-50 text-{$color}-700 ring-{$color}-100";
                    $classes[] = "dark:bg-{$color}-900/20 dark:text-{$color}-400 dark:ring-{$color}-800/30";
                }
                break;
            default:
                if ($active) {
                    $primaryColor = $colors['primary'] ?? 'blue';
                    $classes[] = "bg-{$primaryColor}-100 text-{$primaryColor}-800 ring-{$primaryColor}-200";
                    $classes[] = "dark:bg-{$primaryColor}-900/30 dark:text-{$primaryColor}-300 dark:ring-{$primaryColor}-700/50";
                    $classes[] = "shadow-{$primaryColor}-500/20 dark:shadow-{$primaryColor}-400/30";
                } else {
                    $secondaryColor = $colors['secondary'] ?? 'gray';
                    $classes[] = "bg-{$secondaryColor}-100 text-{$secondaryColor}-800 ring-{$secondaryColor}-200";
                    $classes[] = "dark:bg-{$secondaryColor}-800/30 dark:text-{$secondaryColor}-300 dark:ring-{$secondaryColor}-700/50";
                }
                break;
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Get loading indicator classes with theming
     */
    public function getLoadingClasses(): string
    {
        $classes = [];
        $colors = $this->themeConfig['colors'] ?? [];
        $style = $this->themeConfig['style'] ?? [];

        $classes[] = 'absolute inset-0 flex items-center justify-center bg-white/90 backdrop-blur-sm z-20';
        $classes[] = 'opacity-0 pointer-events-none transition-all duration-200 ease-in-out';
        $classes[] = 'wire:loading:opacity-100 wire:loading:pointer-events-auto';
        $classes[] = $style['radius'] ?? 'rounded-md';

        return implode(' ', array_unique($classes));
    }

    /**
     * Get spinner classes with theming
     */
    public function getSpinnerClasses(): string
    {
        $colors = $this->themeConfig['colors'] ?? [];
        $primaryColor = $colors['primary'] ?? 'blue';

        return "animate-spin rounded-full h-6 w-6 border-b-2 border-{$primaryColor}-500";
    }

    /**
     * Generate CSS variables for dynamic theming
     */
    public function generateCssVariables(): string
    {
        $colors = $this->themeConfig['colors'] ?? [];
        $style = $this->themeConfig['style'] ?? [];

        $css = ':root {';

        foreach ($colors as $name => $color) {
            $css .= "--tabs-color-{$name}: {$color};";
        }

        foreach ($style as $name => $value) {
            $css .= "--tabs-style-{$name}: {$value};";
        }

        $css .= '}';

        return $css;
    }

    /**
     * Get layout configuration for frontend
     */
    public function getLayoutConfig(): array
    {
        return [
            'position' => $this->config['position'] ?? 'top',
            'orientation' => $this->config['orientation'] ?? 'horizontal',
            'scrollable' => $this->config['scrollable'] ?? true,
            'sticky' => $this->config['sticky'] ?? false,
            'full_width' => $this->config['full_width'] ?? false,
            'justify' => $this->config['justify'] ?? 'start',
            'theme' => $this->themeConfig,
        ];
    }

    /**
     * Validate layout configuration
     */
    public function validateConfig(): array
    {
        $issues = [];

        $validPositions = ['top', 'bottom', 'left', 'right'];
        if (!in_array($this->config['position'] ?? 'top', $validPositions)) {
            $issues[] = 'Invalid position: must be one of ' . implode(', ', $validPositions);
        }

        $validOrientations = ['horizontal', 'vertical'];
        if (!in_array($this->config['orientation'] ?? 'horizontal', $validOrientations)) {
            $issues[] = 'Invalid orientation: must be one of ' . implode(', ', $validOrientations);
        }

        $validJustify = ['start', 'center', 'end', 'between', 'around', 'evenly'];
        if (!in_array($this->config['justify'] ?? 'start', $validJustify)) {
            $issues[] = 'Invalid justify: must be one of ' . implode(', ', $validJustify);
        }

        return $issues;
    }

    /**
     * Get responsive classes for mobile optimization
     */
    public function getResponsiveClasses(): string
    {
        $classes = [];
        $position = $this->config['position'] ?? 'top';

        // Mobile-first responsive behavior
        if ($position === 'left' || $position === 'right') {
            // Stack vertically on mobile, side layout on desktop
            $classes[] = 'flex-col md:flex-row';
            if ($position === 'right') {
                $classes[] = 'md:flex-row-reverse';
            }
        }

        // Scrollable tabs on mobile
        if ($this->config['scrollable'] ?? true) {
            $classes[] = 'overflow-x-auto md:overflow-x-visible';
        }

        return implode(' ', array_unique($classes));
    }

    private function logLayout(string $event, array $context = []): void
    {
        if (config('laravel-tabs.development.debug_mode', false)) {
            Log::debug("Tabs Layout: {$event}", array_merge([
                'layout_config' => $this->config,
                'theme_config' => $this->themeConfig,
                'timestamp' => now()->toISOString(),
            ], $context));
        }
    }
}
