<?php

declare(strict_types=1);

namespace Hdaklu\NapTab\Services;

use Hdaklu\NapTab\UI\Tab;
use Illuminate\Support\Collection;

/**
 * Accessibility manager for tabs with ARIA, screen reader, and keyboard support
 */
class TabsAccessibilityManager
{
    private array $config;

    public function __construct()
    {
        $this->config = config('laravel-tabs.accessibility', []);
    }

    /**
     * Get ARIA attributes for tab container
     */
    public function getContainerAttributes(): array
    {
        if (!($this->config['aria_labels'] ?? true)) {
            return [];
        }

        return [
            'role' => 'tabpanel',
            'aria-label' => 'Tab interface',
            'aria-live' => 'polite',
            'aria-atomic' => 'false',
        ];
    }

    /**
     * Get ARIA attributes for tab navigation
     */
    public function getNavigationAttributes(): array
    {
        if (!($this->config['aria_labels'] ?? true)) {
            return [];
        }

        return [
            'role' => 'tablist',
            'aria-label' => 'Tab navigation',
            'aria-orientation' => config('laravel-tabs.layout.orientation', 'horizontal'),
        ];
    }

    /**
     * Get ARIA attributes for individual tab button
     */
    public function getTabAttributes(Tab $tab, bool $active, int $position, int $total): array
    {
        $attributes = [];

        if ($this->config['aria_labels'] ?? true) {
            $attributes['role'] = 'tab';
            $attributes['aria-selected'] = $active ? 'true' : 'false';
            $attributes['aria-controls'] = 'tabpanel-' . $tab->getId();
            $attributes['id'] = 'tab-' . $tab->getId();
            $attributes['aria-posinset'] = (string) $position;
            $attributes['aria-setsize'] = (string) $total;

            // Add description for screen readers
            if ($tab->getBadge()) {
                $attributes['aria-describedby'] = 'tab-badge-' . $tab->getId();
            }

            if ($tab->isDisabled()) {
                $attributes['aria-disabled'] = 'true';
            }
        }

        if ($this->config['tab_index_management'] ?? true) {
            $attributes['tabindex'] = $active ? '0' : '-1';
        }

        return $attributes;
    }

    /**
     * Get ARIA attributes for tab content panel
     */
    public function getContentPanelAttributes(Tab $tab, bool $active): array
    {
        if (!($this->config['aria_labels'] ?? true)) {
            return ['id' => 'tabpanel-' . $tab->getId()];
        }

        return [
            'id' => 'tabpanel-' . $tab->getId(),
            'role' => 'tabpanel',
            'aria-labelledby' => 'tab-' . $tab->getId(),
            'aria-hidden' => $active ? 'false' : 'true',
            'tabindex' => '0',
        ];
    }

    /**
     * Get keyboard navigation attributes
     */
    public function getKeyboardAttributes(): array
    {
        if (!($this->config['keyboard_navigation'] ?? true)) {
            return [];
        }

        return [
            'data-keyboard-navigation' => 'true',
            'data-keyboard-shortcuts' => json_encode([
                'ArrowLeft' => 'previous',
                'ArrowRight' => 'next',
                'ArrowUp' => 'previous',
                'ArrowDown' => 'next',
                'Home' => 'first',
                'End' => 'last',
                'Enter' => 'activate',
                'Space' => 'activate',
            ]),
        ];
    }

    /**
     * Generate screen reader announcements
     */
    public function generateScreenReaderContent(Tab $tab, bool $active, int $position, int $total): array
    {
        if (!($this->config['screen_reader'] ?? true)) {
            return [];
        }

        $announcements = [];

        // Tab description
        $description = $tab->getLabel();
        if ($tab->getBadge()) {
            $description .= ', ' . $tab->getBadge();
        }
        if ($tab->isDisabled()) {
            $description .= ', disabled';
        }
        $description .= ', tab ' . $position . ' of ' . $total;

        $announcements['tab-description'] = $description;

        // Loading announcement
        $announcements['loading'] = 'Loading ' . $tab->getLabel() . ' content';

        // Content loaded announcement
        $announcements['content-loaded'] = $tab->getLabel() . ' content loaded';

        // Error announcement
        $announcements['error'] = 'Error loading ' . $tab->getLabel() . '. Please try again.';

        return $announcements;
    }

    /**
     * Get focus management JavaScript
     */
    public function generateFocusManagementScript(): string
    {
        if (!($this->config['focus_management'] ?? true)) {
            return '';
        }

        return "
            window.TabsAccessibility = {
                config: " . json_encode($this->config) . ",
                
                // Initialize accessibility features
                init: function() {
                    this.setupKeyboardNavigation();
                    this.setupFocusManagement();
                    this.setupScreenReaderSupport();
                },
                
                // Setup keyboard navigation
                setupKeyboardNavigation: function() {
                    if (!this.config.keyboard_navigation) return;
                    
                    document.addEventListener('keydown', (e) => {
                        const activeTab = document.querySelector('[role=\"tab\"][aria-selected=\"true\"]');
                        if (!activeTab) return;
                        
                        const tabList = activeTab.closest('[role=\"tablist\"]');
                        if (!tabList) return;
                        
                        const tabs = Array.from(tabList.querySelectorAll('[role=\"tab\"]:not([aria-disabled=\"true\"])'));
                        const currentIndex = tabs.indexOf(activeTab);
                        
                        let targetIndex = currentIndex;
                        let shouldPreventDefault = true;
                        
                        switch (e.key) {
                            case 'ArrowLeft':
                            case 'ArrowUp':
                                targetIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
                                break;
                            case 'ArrowRight':
                            case 'ArrowDown':
                                targetIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
                                break;
                            case 'Home':
                                targetIndex = 0;
                                break;
                            case 'End':
                                targetIndex = tabs.length - 1;
                                break;
                            case 'Enter':
                            case ' ':
                                this.activateTab(activeTab);
                                break;
                            default:
                                shouldPreventDefault = false;
                        }
                        
                        if (shouldPreventDefault) {
                            e.preventDefault();
                            if (targetIndex !== currentIndex) {
                                this.focusTab(tabs[targetIndex]);
                            }
                        }
                    });
                },
                
                // Focus management
                focusTab: function(tab) {
                    if (!tab) return;
                    
                    // Update tabindex
                    const tabList = tab.closest('[role=\"tablist\"]');
                    tabList.querySelectorAll('[role=\"tab\"]').forEach(t => {
                        t.tabIndex = -1;
                    });
                    tab.tabIndex = 0;
                    tab.focus();
                },
                
                // Activate tab
                activateTab: function(tab) {
                    if (!tab || tab.getAttribute('aria-disabled') === 'true') return;
                    
                    // Trigger click event
                    tab.click();
                    
                    // Announce to screen readers
                    this.announceToScreenReader('Activated ' + tab.textContent.trim());
                },
                
                // Screen reader support
                setupScreenReaderSupport: function() {
                    if (!this.config.screen_reader) return;
                    
                    // Create live region for announcements
                    if (!document.getElementById('tabs-sr-live')) {
                        const liveRegion = document.createElement('div');
                        liveRegion.id = 'tabs-sr-live';
                        liveRegion.setAttribute('aria-live', 'polite');
                        liveRegion.setAttribute('aria-atomic', 'true');
                        liveRegion.className = 'sr-only';
                        liveRegion.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
                        document.body.appendChild(liveRegion);
                    }
                    
                    // Listen for tab changes
                    document.addEventListener('tabs:switch', (e) => {
                        const tabLabel = e.detail.tabLabel || 'Tab';
                        this.announceToScreenReader('Switched to ' + tabLabel);
                    });
                    
                    // Listen for loading states
                    document.addEventListener('tabs:loading', (e) => {
                        const tabLabel = e.detail.tabLabel || 'Tab';
                        this.announceToScreenReader('Loading ' + tabLabel + ' content');
                    });
                    
                    // Listen for content loaded
                    document.addEventListener('tabs:loaded', (e) => {
                        const tabLabel = e.detail.tabLabel || 'Tab';
                        this.announceToScreenReader(tabLabel + ' content loaded');
                    });
                    
                    // Listen for errors
                    document.addEventListener('tabs:error', (e) => {
                        const tabLabel = e.detail.tabLabel || 'Tab';
                        this.announceToScreenReader('Error loading ' + tabLabel + '. Please try again.');
                    });
                },
                
                // Announce to screen readers
                announceToScreenReader: function(message) {
                    if (!this.config.screen_reader) return;
                    
                    const liveRegion = document.getElementById('tabs-sr-live');
                    if (liveRegion) {
                        liveRegion.textContent = message;
                        
                        // Clear after announcement
                        setTimeout(() => {
                            liveRegion.textContent = '';
                        }, 1000);
                    }
                },
                
                // Check for reduced motion preference
                respectsReducedMotion: function() {
                    if (this.config.reduced_motion === 'ignore') return false;
                    if (this.config.reduced_motion === 'force') return true;
                    
                    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                },
                
                // Get appropriate transition duration based on motion preference
                getTransitionDuration: function() {
                    return this.respectsReducedMotion() ? 0 : 200;
                }
            };
            
            // Initialize when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (window.TabsAccessibility) {
                    window.TabsAccessibility.init();
                }
            });
        ";
    }

    /**
     * Generate CSS for accessibility features
     */
    public function generateAccessibilityCSS(): string
    {
        $css = "
            /* Screen reader only content */
            .sr-only {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                padding: 0 !important;
                margin: -1px !important;
                overflow: hidden !important;
                clip: rect(0, 0, 0, 0) !important;
                white-space: nowrap !important;
                border: 0 !important;
            }
            
            /* Skip link for keyboard users */
            .tabs-skip-link {
                position: absolute;
                top: -40px;
                left: 6px;
                z-index: 1000;
                background: #000;
                color: #fff;
                padding: 8px 16px;
                text-decoration: none;
                border-radius: 4px;
                transition: top 0.2s ease;
            }
            
            .tabs-skip-link:focus {
                top: 6px;
            }
            
            /* High contrast mode */
            @media (prefers-contrast: high) {
                [role=\"tab\"][aria-selected=\"true\"] {
                    border: 2px solid currentColor !important;
                }
                
                [role=\"tab\"]:focus {
                    outline: 3px solid currentColor !important;
                    outline-offset: 2px !important;
                }
            }
            
            /* Focus indicators */
            [role=\"tab\"]:focus {
                outline: 2px solid #2563eb;
                outline-offset: 2px;
                z-index: 1;
            }
            
            /* Reduced motion */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            }
        ";

        // Add high contrast support if enabled
        if ($this->config['high_contrast'] ?? false) {
            $css .= "
                /* High contrast theme */
                @media (prefers-color-scheme: dark) {
                    [role=\"tab\"] {
                        border-color: #fff !important;
                        color: #fff !important;
                    }
                    
                    [role=\"tab\"][aria-selected=\"true\"] {
                        background: #fff !important;
                        color: #000 !important;
                    }
                }
            ";
        }

        return $css;
    }

    /**
     * Get skip navigation link
     */
    public function getSkipNavigationLink(string $targetId): string
    {
        if (!($this->config['keyboard_shortcuts'] ?? true)) {
            return '';
        }

        return "<a href=\"#{$targetId}\" class=\"tabs-skip-link sr-only focus:not-sr-only\">Skip to tab content</a>";
    }

    /**
     * Validate accessibility configuration
     */
    public function validateConfig(): array
    {
        $issues = [];

        if (!($this->config['aria_labels'] ?? true)) {
            $issues[] = 'ARIA labels are disabled - this may affect screen reader users';
        }

        if (!($this->config['keyboard_navigation'] ?? true)) {
            $issues[] = 'Keyboard navigation is disabled - this may affect users who cannot use a mouse';
        }

        if (!($this->config['screen_reader'] ?? true)) {
            $issues[] = 'Screen reader support is disabled';
        }

        return $issues;
    }

    /**
     * Get accessibility configuration for frontend
     */
    public function getAccessibilityConfig(): array
    {
        return [
            'aria_labels' => $this->config['aria_labels'] ?? true,
            'screen_reader' => $this->config['screen_reader'] ?? true,
            'keyboard_navigation' => $this->config['keyboard_navigation'] ?? true,
            'focus_management' => $this->config['focus_management'] ?? true,
            'high_contrast' => $this->config['high_contrast'] ?? false,
            'reduced_motion' => $this->config['reduced_motion'] ?? 'respect',
            'keyboard_shortcuts' => $this->config['keyboard_shortcuts'] ?? true,
        ];
    }

    /**
     * Generate structured data for tab content (for SEO/accessibility)
     */
    public function generateStructuredData(Collection $tabs, string $activeTab): array
    {
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPageElement',
            'name' => 'Tab Interface',
            'description' => 'Interactive tabbed content interface',
            'accessibilityFeature' => [
                'keyboardNavigable',
                'screenReaderSupported',
                'ariaLabels',
            ],
            'hasPart' => [],
        ];

        foreach ($tabs as $tab) {
            $structuredData['hasPart'][] = [
                '@type' => 'WebPageElement',
                'name' => $tab->getLabel(),
                'identifier' => $tab->getId(),
                'isAccessibleForFree' => true,
                'inLanguage' => 'en',
            ];
        }

        return $structuredData;
    }
}