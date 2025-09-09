<div class="nap-tab-container w-full" role="tabpanel" aria-label="Tabs interface">
    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="{{ $spacing['tab_gap'] ?? 'gap-2' }} {{ $spacing['nav_padding'] ?? 'px-1' }} -mb-px flex w-full overflow-x-auto"
            aria-label="Tabs" role="tablist">
            @foreach ($tabs as $tab)
                <button type="button" wire:click="switchTab('{{ $tab->getId() }}')" wire:loading.attr="disabled"
                    wire:target="switchTab"
                    class="@php
$config = app('naptab.config')->toArray();
                        $colors = $config['colors'];
                        $styles = $config['styles'];
                        $shadows = $styles['shadows'];
                        $borders = $styles['borders'];
                        $transitions = $styles['transitions'];
                        $spacing = $styles['spacing'];
                        $primaryColor = $colors['primary'];
                        $secondaryColor = $colors['secondary'];
                        
                        // Shadow classes
                        $shadowEnabled = $shadows['enabled'];
                        
                        $tabShadow = $shadowEnabled ? $shadows['tab_shadow'] : '';
                        $tabShadowColor = $shadowEnabled ? $shadows['tab_shadow_color'] : '';
                        $hoverShadow = $shadowEnabled ? $shadows['hover_shadow'] : '';
                        $hoverShadowColor = $shadowEnabled ? $shadows['hover_shadow_color'] : '';
                        $focusShadow = $shadowEnabled ? $shadows['focus_shadow'] : '';
                        $focusShadowColor = $shadowEnabled ? $shadows['focus_shadow_color'] : '';

                        // Border and transition classes
                        $borderWidth = $borders['border_width'];
                        $doubleBorder = $borders['double_border'];
                        $transitionDuration = $transitions['duration'];
                        $transitionTiming = $transitions['timing'];
                        $borderRadius = $styles['border_radius'];
                        $currentStyle = $styles['current_style'];

                        // Active tab classes - handle different styles
                        if ($currentStyle === 'minimal') {
                            // MINIMAL: Clean border-only style with primary color text/icons
                            $activeClasses = 'border-0 text-' . $primaryColor . '-600 dark:text-' . $primaryColor . '-400 font-semibold';
                        } else {
                            // MODERN/SHARP: Rich gradient background
                            $activeClasses = 'border-0 text-' . $primaryColor . '-700 dark:text-' . $primaryColor . '-300 font-semibold bg-gradient-to-t from-' . $primaryColor . '-50/80 to-transparent dark:from-' . $primaryColor . '-900/30';
                        }

                        // Apply shadows to active tab when enabled
                        if ($shadowEnabled && !empty($tabShadow) && $tabShadow !== 'shadow-none') {
                            $activeClasses .= ' ' . $tabShadow;
                            if (!empty($tabShadowColor)) {
                                $activeClasses .= ' ' . $tabShadowColor;
                            }
                        }


                        // Inactive tab classes
                        $inactiveClasses = $borderWidth . ' border-transparent text-' . $secondaryColor . '-600 dark:text-' . $secondaryColor . '-400 hover:text-' . $secondaryColor . '-900 dark:hover:text-white hover:border-' . $secondaryColor . '-300 dark:hover:border-' . $secondaryColor . '-600 hover:bg-gradient-to-t hover:from-' . $secondaryColor . '-50/80 hover:to-transparent dark:hover:from-' . $secondaryColor . '-800/50';

                        // Apply hover shadows to inactive tabs when enabled
                        if ($shadowEnabled && !empty($hoverShadow) && $hoverShadow !== 'shadow-none') {
                            $inactiveClasses .= ' hover:' . $hoverShadow;
                            if (!empty($hoverShadowColor)) {
                                $inactiveClasses .= ' hover:' . $hoverShadowColor;
                            }
                        }

                        echo $activeTab === $tab->getId() ? $activeClasses : $inactiveClasses; @endphp {{ $tab->isDisabled() ? 'opacity-40 cursor-not-allowed pointer-events-none grayscale' : ($activeTab === $tab->getId() ? 'cursor-default tab-active-premium' : 'cursor-pointer tab-hover-simple') }} {{ $spacing['tab_padding'] }} {{ $transitions['duration'] }} {{ $transitions['timing'] }} tab-button {{ $styles['border_radius'] }} group relative flex-shrink-0 whitespace-nowrap overflow-hidden text-sm font-medium transition-all focus:outline-none"
                    role="tab" aria-selected="{{ $activeTab === $tab->getId() ? 'true' : 'false' }}"
                    aria-controls="tabpanel-{{ $tab->getId() }}"
                    tabindex="{{ $activeTab === $tab->getId() ? '0' : '-1' }}"
                    @if ($tab->isDisabled()) disabled @endif>
                    <div class="{{ $spacing['inner_gap'] }} flex items-center justify-center text-center min-w-0 max-w-full">
                        {{-- Loading spinner --}}
                        <div wire:loading wire:target="switchTab('{{ $tab->getId() }}')" class="animate-spin">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>

                        {{-- Tab icon --}}
                        <div wire:loading.remove wire:target="switchTab('{{ $tab->getId() }}')">
                            @if ($tab->getIcon())
                                <x-dynamic-component :component="'heroicon-o-' . $tab->getIcon()" class="h-4 w-4 flex-shrink-0" />
                            @endif
                        </div>

                        <span class="truncate text-center leading-tight flex-1 min-w-0">{{ $tab->getLabel() }}</span>

                        {{-- Badge --}}
                        @if ($tab->getBadge())
                            @php
                                $badgeRadius = $styles['badge_radius'];
                                $badgeSize = $styles['badge_size'];
                                $primaryColor = $colors['primary'];
                                $secondaryColor = $colors['secondary'];
                                $shadowEnabled = $shadows['enabled'];
                                $transitionDuration = $transitions['duration'];

                                $activeBadgeClasses =
                                    'bg-' .
                                    $primaryColor .
                                    '-100 text-' .
                                    $primaryColor .
                                    '-800 ring-' .
                                    $primaryColor .
                                    '-200 dark:bg-' .
                                    $primaryColor .
                                    '-900/30 dark:text-' .
                                    $primaryColor .
                                    '-300 dark:ring-' .
                                    $primaryColor .
                                    '-700/50';
                                $inactiveBadgeClasses =
                                    'bg-' .
                                    $secondaryColor .
                                    '-100 text-' .
                                    $secondaryColor .
                                    '-800 ring-' .
                                    $secondaryColor .
                                    '-200 dark:bg-' .
                                    $secondaryColor .
                                    '-800/30 dark:text-' .
                                    $secondaryColor .
                                    '-300 dark:ring-' .
                                    $secondaryColor .
                                    '-700/50';

                                if ($shadowEnabled) {
                                    $activeBadgeClasses .=
                                        ' shadow-sm shadow-' .
                                        $primaryColor .
                                        '-500/15 dark:shadow-' .
                                        $primaryColor .
                                        '-400/20';
                                    $inactiveBadgeClasses .=
                                        ' shadow-sm shadow-' .
                                        $secondaryColor .
                                        '-500/10 dark:shadow-' .
                                        $secondaryColor .
                                        '-400/15';
                                }
                            @endphp
                            <span
                                class="{{ $activeTab === $tab->getId() ? $activeBadgeClasses : $inactiveBadgeClasses }} {{ $badgeRadius }} {{ $badgeSize }} {{ $transitionDuration }} inline-flex flex-shrink-0 items-center font-medium ring-1 ring-inset transition-all hover:scale-105 group-hover:animate-pulse">
                                {{ $tab->getBadge() }}
                            </span>
                        @endif
                    </div>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab Content Area --}}
    <div
        class="{{ $spacing['content_margin'] ?? 'mt-6' }} items-top relative flex min-h-[400px] justify-center overflow-hidden">
        {{-- Loading indicator --}}
        <div wire:loading wire:target="switchTab"
            class="wire:loading:opacity-100 wire:loading:pointer-events-auto pointer-events-none absolute inset-0 z-20 flex items-center justify-center bg-white/90 opacity-0 backdrop-blur-sm transition-all duration-200 ease-in-out dark:bg-gray-900/90">
            <div
                class="wire:loading:scale-100 scale-95 transform text-center transition-transform duration-200 ease-out">
                <div
                    class="mx-auto mb-3 h-6 w-6 animate-spin rounded-full border-b-2 border-blue-500 dark:border-blue-400">
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Loading...</p>
            </div>
        </div>

        {{-- Tab content --}}
        <div class="nap-tab-content animate-fadeInUp w-full transition-opacity duration-200 ease-in-out"
            wire:loading.class="opacity-30" wire:target="switchTab" id="tabpanel-{{ $activeTab }}" role="tabpanel"
            aria-labelledby="tab-{{ $activeTab }}" tabindex="0">
            @php
                $activeTabObj = $tabs->get($activeTab);
            @endphp

            @if ($activeTabObj)
                <x-tabs-container.tab-content :tab="$activeTabObj" :active="true" :loaded="isset($loadedTabs[$activeTab])" :error="$tabErrors[$activeTab] ?? null" />
            @else
                <div class="py-12 text-center text-gray-500 dark:text-gray-400" role="status" aria-live="polite">
                    <p>No active tab selected.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Inline styles and scripts within the single root element --}}
    @if ($navigationScript ?? false)
        <script>
            {!! $navigationScript !!}
        </script>
    @endif

    <style>
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

        @keyframes shimmer {
            0% {
                background-position: -200px 0;
            }

            100% {
                background-position: calc(200px + 100%) 0;
            }
        }

        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 5px rgba(59, 130, 246, 0.4);
            }
            50% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.6), 0 0 30px rgba(59, 130, 246, 0.4);
            }
        }


        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 200ms ease-in-out;
        }

        .animate-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            background-size: 200px 100%;
            animation: shimmer 2s infinite;
        }

        .animate-glow {
            animation: glow 2s ease-in-out infinite;
        }

        .animate-slideInFromTop {
            animation: slideInFromTop 200ms ease-out;
        }


        /* Simple hover effects */
        .tab-hover-simple {
            transition: background-color 200ms ease, transform 150ms ease;
        }


        /* Clean Active tab styling - simple underline */
        .tab-active-premium {
            position: relative;
            contain: layout;
            overflow: hidden;
        }

        .tab-active-premium::after {
            content: '';
            position: absolute;
            bottom: -1px;
            inset-inline: 8px;
            height: 2px;
            background: currentColor;
            border-radius: 1px;
            opacity: 0.9;
        }


        /* Anti-flicker optimization */
        .tab-button {
            transform: translateZ(0);
            backface-visibility: hidden;
            will-change: auto;
            isolation: isolate;
        }

        /* Smooth focus states */
        .tab-focus-ring:focus-visible {
            outline: none;
            ring-offset: 2px;
            ring-color: rgb(59 130 246 / 0.5);
            ring-width: 2px;
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            .animate-fadeInUp,
            .animate-shimmer,
            .animate-glow,
            .animate-slideInFromTop {
                animation: none !important;
            }

            .tab-button,
            .tab-hover-simple {
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .tab-active-premium::after {
                opacity: 1;
                height: 3px;
            }
        }
    </style>
</div>
