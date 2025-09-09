<div class="nap-tab-container w-full" role="tabpanel" aria-label="Tabs interface">
    {{-- Tab Navigation with Device Detection --}}
    @php
        $agent = app('agent');
        $mobileConfig = $styles['mobile'] ?? [];
        $useModalOnMobile = $mobileConfig['modal_navigation'] ?? false;
        
        // Detect device type
        $isMobile = $agent->isMobile() && !$agent->isTablet();
        $isTablet = $agent->isTablet();
        $isDesktop = $agent->isDesktop() || (!$isMobile && !$isTablet);
    @endphp
    
    <div class="border-b border-gray-200 dark:border-gray-700">
        {{-- Device-Specific Navigation --}}
        @if($isMobile && $useModalOnMobile)
            @include('naptab::navigation.mobile-modal', ['tabs' => $tabs, 'activeTab' => $activeTab, 'styles' => $styles, 'spacing' => $spacing])
        @elseif($isMobile)
            @include('naptab::navigation.mobile-scroll', ['tabs' => $tabs, 'activeTab' => $activeTab, 'styles' => $styles, 'spacing' => $spacing])
        @else
            @include('naptab::navigation.desktop', ['tabs' => $tabs, 'activeTab' => $activeTab, 'styles' => $styles, 'spacing' => $spacing])
        @endif

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
        
        /* Tab styling for all screen sizes */
        .tab-button {
            min-width: fit-content;
            flex-shrink: 0;
            scroll-snap-align: center;
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
