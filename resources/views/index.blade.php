<div class="nap-tab-container w-full" role="tabpanel" aria-label="Tabs interface">
    {{-- Tab Navigation --}}
    @php
        $mobileConfig = $styles['mobile'] ?? [];
        $useModalOnMobile = $mobileConfig['modal_navigation'] ?? false;
    @endphp
    
    <div class="border-b border-gray-200 dark:border-gray-700">
        {{-- Mobile Modal Button (only show when modal navigation is enabled) --}}
        @if($useModalOnMobile)
            <div class="md:hidden mb-4" x-data="{ modalOpen: false }">
                <button x-on:click="modalOpen = !modalOpen; if (modalOpen) { document.body.style.overflow = 'hidden'; } else { document.body.style.overflow = ''; }" 
                    class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 active:scale-[0.98]">
                    
                    {{-- Active tab content --}}
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        @foreach($tabs as $tab)
                            @if($activeTab === $tab->getId())
                                {{-- Tab icon --}}
                                @if($tab->getIcon())
                                    <div class="flex-shrink-0">
                                        <x-dynamic-component :component="'heroicon-o-' . $tab->getIcon()" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                @endif
                                
                                {{-- Tab label and badge --}}
                                <div class="min-w-0 flex-1 flex items-center space-x-2">
                                    <span class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $tab->getLabel() }}
                                    </span>
                                    @if($tab->getBadge())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 flex-shrink-0">
                                            {{ $tab->getBadge() }}
                                        </span>
                                    @endif
                                </div>
                                @break
                            @endif
                        @endforeach
                    </div>
                    
                    {{-- Hamburger menu icon --}}
                    <div class="flex-shrink-0 ml-3">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </div>
                </button>
                
                {{-- Mobile Modal Overlay --}}
                <div x-show="modalOpen" 
                     x-transition.opacity.duration.300ms
                     class="fixed inset-0 z-50 md:hidden"
                     style="display: none;">
                    
                    {{-- Backdrop --}}
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" 
                         x-on:click="modalOpen = false; document.body.style.overflow = ''"></div>
                    
                    {{-- Modal --}}
                    <div class="fixed inset-x-0 bottom-0 bg-white dark:bg-gray-900 rounded-t-xl shadow-2xl max-h-[80vh] overflow-hidden"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="transform translate-y-full"
                         x-transition:enter-end="transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="transform translate-y-0"
                         x-transition:leave-end="transform translate-y-full">
                        
                        {{-- Modal Header --}}
                        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Select Tab</h2>
                            <button x-on:click="modalOpen = false; document.body.style.overflow = ''" 
                                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        {{-- Tab List --}}
                        <div class="overflow-y-auto max-h-[60vh]">
                            @foreach($tabs as $tab)
                                @php
                                    $baseRoute = $this->baseRoute();
                                    $hasUrlNavigation = !empty($baseRoute);
                                    $tabUrl = $hasUrlNavigation ? rtrim($baseRoute, '/') . '/' . $tab->getId() : null;
                                    $isActive = $activeTab === $tab->getId();
                                @endphp
                                
                                @if($hasUrlNavigation && $tabUrl)
                                    <a href="{{ $tabUrl }}" wire:navigate
                                @else
                                    <button type="button" wire:click="switchTab('{{ $tab->getId() }}')"
                                @endif
                                    x-on:click="modalOpen = false; document.body.style.overflow = ''"
                                    class="w-full flex items-center space-x-3 px-4 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors {{ $isActive ? 'bg-blue-50 dark:bg-blue-900/20 border-r-2 border-blue-500' : '' }}"
                                    {{ $tab->isDisabled() ? 'disabled' : '' }}>
                                    
                                    {{-- Tab icon --}}
                                    @if($tab->getIcon())
                                        <div class="flex-shrink-0">
                                            <x-dynamic-component :component="'heroicon-o-' . $tab->getIcon()" 
                                                class="h-5 w-5 {{ $isActive ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}" />
                                        </div>
                                    @endif
                                    
                                    {{-- Tab content --}}
                                    <div class="min-w-0 flex-1 flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <span class="font-medium {{ $isActive ? 'text-blue-900 dark:text-blue-100' : 'text-gray-900 dark:text-gray-100' }} truncate block">
                                                {{ $tab->getLabel() }}
                                            </span>
                                        </div>
                                        
                                        {{-- Badge --}}
                                        @if($tab->getBadge())
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $isActive ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }} flex-shrink-0">
                                                {{ $tab->getBadge() }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    {{-- Active indicator --}}
                                    @if($isActive)
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                @if($hasUrlNavigation && $tabUrl)
                                </a>
                                @else
                                </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Desktop Navigation & Mobile Scroll Navigation --}}
        <nav class="{{ $spacing['tab_gap'] ?? 'gap-2' }} {{ $spacing['nav_padding'] ?? 'px-1' }} -mb-px flex w-full 
            @if($useModalOnMobile) hidden md:flex @else flex @endif
            overflow-x-auto scrollbar-hide scroll-smooth snap-x snap-mandatory"
            aria-label="Tabs" role="tablist" 
            x-data="{ 
                scrollToActive() {
                    const activeTab = this.$el.querySelector('[aria-selected=\"true\"]');
                    if (activeTab && window.innerWidth < 768) {
                        activeTab.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'nearest', 
                            inline: 'center' 
                        });
                    }
                }
            }"
            x-init="
                // Auto-scroll to active tab on mobile when not using modal
                if (!{{ $useModalOnMobile ? 'true' : 'false' }}) {
                    $nextTick(() => scrollToActive());
                }
                
                // Listen for tab changes and auto-scroll on mobile
                $wire.on('tab-changed', () => {
                    if (!{{ $useModalOnMobile ? 'true' : 'false' }} && window.innerWidth < 768) {
                        setTimeout(() => scrollToActive(), 100);
                    }
                });
                
                // Auto-scroll on window resize if mobile
                window.addEventListener('resize', () => {
                    if (!{{ $useModalOnMobile ? 'true' : 'false' }} && window.innerWidth < 768) {
                        scrollToActive();
                    }
                });
            "
            @foreach ($tabs as $tab)
                @php
                    $baseRoute = $this->baseRoute();
                    $hasUrlNavigation = !empty($baseRoute);
                    $tabUrl = $hasUrlNavigation ? rtrim($baseRoute, '/') . '/' . $tab->getId() : null;
                @endphp
                
                @if($hasUrlNavigation && $tabUrl)
                    <a href="{{ $tabUrl }}" wire:navigate.hover wire:loading.attr="disabled"
                       wire:target="switchTab" data-tab-id="{{ $tab->getId() }}"
                @else
                    <button type="button" wire:click="switchTab('{{ $tab->getId() }}')" wire:loading.attr="disabled"
                        wire:target="switchTab" data-tab-id="{{ $tab->getId() }}"
                @endif
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

                        echo $activeTab === $tab->getId() ? $activeClasses : $inactiveClasses; @endphp {{ $tab->isDisabled() ? 'opacity-40 cursor-not-allowed pointer-events-none grayscale' : ($activeTab === $tab->getId() ? 'cursor-default tab-active-premium' : 'cursor-pointer tab-hover-simple') }} {{ $spacing['tab_padding'] }} {{ $transitions['duration'] }} {{ $transitions['timing'] }} tab-button {{ $styles['border_radius'] }} group relative flex-shrink-0 overflow-hidden whitespace-nowrap text-sm font-medium transition-all focus:outline-none"
                    role="tab" aria-selected="{{ $activeTab === $tab->getId() ? 'true' : 'false' }}"
                    aria-controls="tabpanel-{{ $tab->getId() }}"
                    tabindex="{{ $activeTab === $tab->getId() ? '0' : '-1' }}"
                    @if ($tab->isDisabled()) disabled @endif>
                    <div
                        class="{{ $spacing['inner_gap'] }} flex min-w-0 max-w-full items-center justify-center text-center">
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
                        <div wire:loading.remove wire:target="switchTab('{{ $tab->getId() }}')" wire:ignore>
                            @if ($tab->getIcon())
                                <x-dynamic-component :component="'heroicon-o-' . $tab->getIcon()" class="h-4 w-4 flex-shrink-0" />
                            @endif
                        </div>

                        <span class="min-w-0 flex-1 truncate text-center leading-tight">{{ $tab->getLabel() }}</span>

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
                            <span x-data="{ active: '{{ $activeTab }}' === '{{ $tab->getId() }}' }"
                                x-init="$watch('$wire.activeTab', value => active = (value === '{{ $tab->getId() }}'))"
                                :class="active ? '{{ $activeBadgeClasses }}' : '{{ $inactiveBadgeClasses }}'"
                                class="{{ $badgeRadius }} {{ $badgeSize }} {{ $transitionDuration }} inline-flex flex-shrink-0 items-center font-medium ring-1 ring-inset transition-all">
                                {{ $tab->getBadge() }}
                            </span>
                        @endif
                    </div>
                @if($hasUrlNavigation && $tabUrl)
                </a>
                @else
                </button>
                @endif
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
        <div class="nap-tab-content w-full transition-opacity duration-200 ease-in-out"
            wire:loading.class="opacity-30" wire:target="switchTab" id="tabpanel-{{ $activeTab }}" role="tabpanel"
            aria-labelledby="tab-{{ $activeTab }}" tabindex="0">
            @php
                $activeTabObj = $tabs->get($activeTab);
            @endphp

            @if ($activeTabObj)
                @include('naptab::tab-content', [
                    'tab' => $activeTabObj, 
                    'active' => true, 
                    'loaded' => isset($loadedTabs[$activeTab]), 
                    'error' => $tabErrors[$activeTab] ?? null
                ])
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
        /* Mobile scrollbar hiding and snap behavior */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Snap to tab items on mobile */
        .snap-x {
            scroll-snap-type: x mandatory;
        }
        
        .snap-mandatory {
            scroll-snap-type: x mandatory;
        }
        
        .tab-button {
            scroll-snap-align: center;
            scroll-snap-stop: normal;
        }
        
        /* Mobile-specific tab styling */
        @media (max-width: 767px) {
            .tab-button {
                min-width: fit-content;
                flex-shrink: 0;
                scroll-snap-align: center;
            }
        }

        /* Simple content transitions - opacity only */
        .nap-tab-content {
            opacity: 1;
            transition: opacity 200ms ease-in-out;
        }

        .nap-tab-content.loading {
            opacity: 0.6;
        }


        /* Tab button smooth transitions */
        .tab-button {
            transition: background-color 200ms ease-in-out, 
                       color 200ms ease-in-out,
                       border-color 200ms ease-in-out,
                       opacity 200ms ease-in-out;
        }
        
        /* Active tab underline smooth transition */
        .tab-active-premium::after {
            transition: opacity 250ms ease-in-out;
        }
        
        /* Simple hover effects */
        .tab-hover-simple {
            transition: background-color 150ms ease-in-out;
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
            .nap-tab-content,
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
