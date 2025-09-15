{{-- Shared Tab Items Partial --}}
@foreach ($tabs as $tab)
    <div wire:key="tab-wrapper-{{ $tab->getId() }}" class="contents">
        @php
            $config = app('naptab.config')->toArray();
            $isRoutable = $this->isRoutable();
            $currentRoute = request()->route();
            $hasActiveTabParam = $currentRoute && in_array('activeTab', $currentRoute->parameterNames());
            $hasUrlNavigation = $isRoutable && $hasActiveTabParam;
            $isActive = $activeTab === $tab->getId();

            $tabUrl = null;
            if ($hasUrlNavigation) {
                try {
                    $routeName = $currentRoute->getName();
                    $routeParams = $currentRoute->parameters();
                    $routeParams['activeTab'] = $tab->getId();

                    if ($routeName) {
                        $tabUrl = route($routeName, $routeParams);
                    } else {
                        // Handle edge case: no route name - build URL from current URI
                        $currentUri = request()->getPathInfo();
                        $baseUri = preg_replace('/\/[^\/]*$/', '', $currentUri);
                        $tabUrl = $baseUri . '/' . $tab->getId();
                    }
                } catch (\Exception $e) {
                    $hasUrlNavigation = false;
                }
            }

            // Extract style configuration
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

            // Border and transition classes
            $borderWidth = $borders['border_width'];
            $transitionDuration = $transitions['duration'];
            $transitionTiming = $transitions['timing'];
            $borderRadius = $styles['border_radius'];
            $currentStyle = $styles['current_style'];

            // Active tab classes - handle different styles
            if ($currentStyle === 'minimal') {
                // MINIMAL: Clean border-only style with primary color text/icons
                $activeClasses =
                    'border-0 text-' . $primaryColor . '-600 dark:text-' . $primaryColor . '-400 font-semibold';
            } elseif ($currentStyle === 'pills') {
                // PILLS: Full border with primary color border and background
                $activeClasses =
                    $borderWidth .
                    ' border-' .
                    $primaryColor .
                    '-500 dark:border-' .
                    $primaryColor .
                    '-400 text-' .
                    $primaryColor .
                    '-700 dark:text-' .
                    $primaryColor .
                    '-300 font-semibold bg-' .
                    $primaryColor .
                    '-50/80 dark:bg-' .
                    $primaryColor .
                    '-900/30';
            } else {
                // MODERN/SHARP: Rich gradient background
                $activeClasses =
                    'border-0 text-' .
                    $primaryColor .
                    '-700 dark:text-' .
                    $primaryColor .
                    '-300 font-semibold bg-gradient-to-t from-' .
                    $primaryColor .
                    '-50/80 to-transparent dark:from-' .
                    $primaryColor .
                    '-900/30';
            }

            // Apply shadows to active tab when enabled
            if ($shadowEnabled && !empty($tabShadow) && $tabShadow !== 'shadow-none') {
                $activeClasses .= ' ' . $tabShadow;
                if (!empty($tabShadowColor)) {
                    $activeClasses .= ' ' . $tabShadowColor;
                }
            }

            // Inactive tab classes - handle different styles
            if ($currentStyle === 'pills') {
                // PILLS: Full border with subtle background
                $inactiveClasses =
                    $borderWidth .
                    ' border-' .
                    $secondaryColor .
                    '-300 dark:border-' .
                    $secondaryColor .
                    '-600 text-' .
                    $secondaryColor .
                    '-600 dark:text-' .
                    $secondaryColor .
                    '-400 hover:text-' .
                    $secondaryColor .
                    '-900 dark:hover:text-white hover:border-' .
                    $secondaryColor .
                    '-400 dark:hover:border-' .
                    $secondaryColor .
                    '-500 hover:bg-' .
                    $secondaryColor .
                    '-50/80 dark:hover:bg-' .
                    $secondaryColor .
                    '-800/50';
            } else {
                // MINIMAL/MODERN/SHARP: Bottom border style
                $inactiveClasses =
                    $borderWidth .
                    ' border-transparent text-' .
                    $secondaryColor .
                    '-600 dark:text-' .
                    $secondaryColor .
                    '-400 hover:text-' .
                    $secondaryColor .
                    '-900 dark:hover:text-white hover:border-' .
                    $secondaryColor .
                    '-300 dark:hover:border-' .
                    $secondaryColor .
                    '-600 hover:bg-gradient-to-t hover:from-' .
                    $secondaryColor .
                    '-50/80 hover:to-transparent dark:hover:from-' .
                    $secondaryColor .
                    '-800/50';
            }

            // Apply hover shadows to inactive tabs when enabled
            if ($shadowEnabled && !empty($hoverShadow) && $hoverShadow !== 'shadow-none') {
                $inactiveClasses .= ' hover:' . $hoverShadow;
                if (!empty($hoverShadowColor)) {
                    $inactiveClasses .= ' hover:' . $hoverShadowColor;
                }
            }
        @endphp

        @if ($hasUrlNavigation && $tabUrl)
        <a href="{{ $tabUrl }}" wire:navigate wire:key="tab-nav-{{ $tab->getId() }}" @else <button type="button"
                wire:click="switchTab('{{ $tab->getId() }}')" wire:key="tab-btn-{{ $tab->getId() }}" @endif
                class="{{ $isActive ? $activeClasses : $inactiveClasses }} {{ $tab->isDisabled() ? 'opacity-40 cursor-not-allowed pointer-events-none grayscale' : ($isActive ? 'cursor-default' . ($currentStyle !== 'pills' ? ' tab-active-premium' : '') : 'cursor-pointer tab-hover-simple') }} {{ $spacing['tab_padding'] }} {{ $transitionDuration }} {{ $transitionTiming }} tab-button {{ $borderRadius }} group relative flex-shrink-0 overflow-hidden whitespace-nowrap text-sm font-medium transition-all focus:outline-none tab-focus-ring focus:ring-{{ $primaryColor }}-500/50"
                id="tab-{{ $tab->getId() }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                aria-controls="panel-{{ $tab->getId() }}"
                tabindex="{{ $isActive ? '0' : '-1' }}"
                {{ $tab->isDisabled() ? 'disabled' : '' }}
                style="{{ isset($styles['tab_custom']) ? $styles['tab_custom'] : '' }}">

                {{-- Tab Content --}}
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

                    <span class="min-w-0 flex-1 truncate text-start leading-tight">{{ $tab->getLabel() }}</span>

                    {{-- Badge --}}
                    @if ($tab->getBadge())
                        @php
                            $badgeRadius = $styles['badge_radius'];
                            $badgeSize = $styles['badge_size'];

                            $activeBadgeClasses =
                                'bg-' .
                                $primaryColor .
                                '-200/70 text-' .
                                $primaryColor .
                                '-900 ring-' .
                                $primaryColor .
                                '-300/50 dark:bg-' .
                                $primaryColor .
                                '-900/30 dark:text-' .
                                $primaryColor .
                                '-300 dark:ring-' .
                                $primaryColor .
                                '-700/50';
                            $inactiveBadgeClasses =
                                'bg-' .
                                $secondaryColor .
                                '-200/60 text-' .
                                $secondaryColor .
                                '-900 ring-' .
                                $secondaryColor .
                                '-300/40 dark:bg-' .
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
                            class="{{ $isActive ? $activeBadgeClasses : $inactiveBadgeClasses }} {{ $badgeRadius }} {{ $badgeSize }} {{ $transitionDuration }} inline-flex flex-shrink-0 items-center font-medium ring-1 ring-inset transition-all">
                            {{ $tab->getBadge() }}
                        </span>
                    @endif
                </div>
                @if ($hasUrlNavigation && $tabUrl)
            </a>
        @else
            </button>
        @endif
    </div>
@endforeach
