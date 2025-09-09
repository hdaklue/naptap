{{-- Mobile Modal Navigation --}}
@php
    $config = app('naptab.config')->toArray();
    $primaryColor = $config['colors']['primary'];
    $secondaryColor = $config['colors']['secondary'];
    $shadowEnabled = $config['styles']['shadows']['enabled'];
@endphp
<div class="mb-4" x-data="{ modalOpen: false }" 
     x-on:keydown.escape.window="if (modalOpen) { modalOpen = false; document.body.style.overflow = ''; }">
    
    {{-- Active Tab Button --}}
    <button x-on:click="modalOpen = !modalOpen; if (modalOpen) { document.body.style.overflow = 'hidden'; } else { document.body.style.overflow = ''; }" 
        class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-{{ $secondaryColor }}-800 border border-{{ $secondaryColor }}-300 dark:border-{{ $secondaryColor }}-600 rounded-lg {{ $shadowEnabled ? 'shadow-sm' : '' }} hover:bg-{{ $secondaryColor }}-50 dark:hover:bg-{{ $secondaryColor }}-700 transition-all duration-200 active:scale-[0.98]">
        
        {{-- Active tab content --}}
        <div class="flex items-center space-x-3 min-w-0 flex-1">
            @foreach($tabs as $tab)
                @if($activeTab === $tab->getId())
                    {{-- Tab icon --}}
                    @if($tab->getIcon())
                        <div class="flex-shrink-0">
                            <x-dynamic-component :component="'heroicon-o-' . $tab->getIcon()" class="h-5 w-5 text-{{ $primaryColor }}-600 dark:text-{{ $primaryColor }}-400" />
                        </div>
                    @endif
                    
                    {{-- Tab label and badge --}}
                    <div class="min-w-0 flex-1 flex items-center space-x-2">
                        <span class="font-medium text-{{ $primaryColor }}-900 dark:text-{{ $primaryColor }}-100 truncate">
                            {{ $tab->getLabel() }}
                        </span>
                        @if($tab->getBadge())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $primaryColor }}-100 text-{{ $primaryColor }}-800 dark:bg-{{ $primaryColor }}-900/50 dark:text-{{ $primaryColor }}-100 flex-shrink-0">
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
            <svg class="w-5 h-5 text-{{ $secondaryColor }}-500 dark:text-{{ $secondaryColor }}-400 transition-transform duration-200" 
                 :class="modalOpen ? 'rotate-90' : ''" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </div>
    </button>
    
    {{-- Mobile Modal Overlay --}}
    <div x-show="modalOpen" 
         x-transition.opacity.duration.300ms
         class="fixed inset-0 z-50"
         style="display: none;">
        
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" 
             x-on:click="modalOpen = false; document.body.style.overflow = ''"></div>
        
        {{-- Modal --}}
        <div class="fixed inset-x-0 bottom-0 bg-white dark:bg-{{ $secondaryColor }}-900 rounded-t-xl {{ $shadowEnabled ? 'shadow-2xl' : 'shadow-lg' }} max-h-[80vh] overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-full"
             x-transition:enter-end="transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0"
             x-transition:leave-end="transform translate-y-full">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-4 border-b border-{{ $secondaryColor }}-200 dark:border-{{ $secondaryColor }}-700">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-{{ $secondaryColor }}-500 dark:text-{{ $secondaryColor }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <h2 class="text-lg font-semibold text-{{ $secondaryColor }}-900 dark:text-{{ $secondaryColor }}-100">
                        Select Tab
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $secondaryColor }}-100 text-{{ $secondaryColor }}-800 dark:bg-{{ $secondaryColor }}-700 dark:text-{{ $secondaryColor }}-200">
                            {{ count($tabs) }}
                        </span>
                    </h2>
                </div>
                <button x-on:click="modalOpen = false; document.body.style.overflow = ''" 
                        class="p-2 -m-2 text-{{ $secondaryColor }}-400 hover:text-{{ $secondaryColor }}-600 dark:hover:text-{{ $secondaryColor }}-300 transition-colors rounded-lg hover:bg-{{ $secondaryColor }}-100 dark:hover:bg-{{ $secondaryColor }}-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            {{-- Tab List --}}
            <div class="overflow-y-auto max-h-[60vh]">
                @foreach($tabs as $tab)
                    @php
                        $config = app('naptab.config')->toArray();
                        $isRoutable = $config['styles']['routing']['enabled'] ?? true;
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
                    @endphp
                    
                    @if($hasUrlNavigation && $tabUrl)
                        <a href="{{ $tabUrl }}" wire:navigate
                    @else
                        <button type="button" wire:click="switchTab('{{ $tab->getId() }}')"
                    @endif
                        x-on:click="modalOpen = false; document.body.style.overflow = ''"
                        class="w-full flex items-center space-x-3 px-4 py-4 text-left hover:bg-{{ $secondaryColor }}-50 dark:hover:bg-{{ $secondaryColor }}-800 transition-colors {{ $isActive ? 'bg-' . $primaryColor . '-50 dark:bg-' . $primaryColor . '-900/20 border-r-2 border-' . $primaryColor . '-500' : '' }}"
                        {{ $tab->isDisabled() ? 'disabled' : '' }}>
                        
                        {{-- Tab icon --}}
                        @if($tab->getIcon())
                            <div class="flex-shrink-0">
                                <x-dynamic-component :component="'heroicon-o-' . $tab->getIcon()" 
                                    class="h-5 w-5 {{ $isActive ? 'text-' . $primaryColor . '-600 dark:text-' . $primaryColor . '-400' : 'text-' . $secondaryColor . '-500 dark:text-' . $secondaryColor . '-400' }}" />
                            </div>
                        @endif
                        
                        {{-- Tab content --}}
                        <div class="min-w-0 flex-1 flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <span class="font-medium {{ $isActive ? 'text-' . $primaryColor . '-900 dark:text-' . $primaryColor . '-100' : 'text-' . $secondaryColor . '-900 dark:text-' . $secondaryColor . '-100' }} truncate block">
                                    {{ $tab->getLabel() }}
                                </span>
                            </div>
                            
                            {{-- Badge --}}
                            @if($tab->getBadge())
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $isActive ? 'bg-' . $primaryColor . '-100 text-' . $primaryColor . '-800 dark:bg-' . $primaryColor . '-800 dark:text-' . $primaryColor . '-200' : 'bg-' . $secondaryColor . '-100 text-' . $secondaryColor . '-800 dark:bg-' . $secondaryColor . '-700 dark:text-' . $secondaryColor . '-200' }} flex-shrink-0">
                                    {{ $tab->getBadge() }}
                                </span>
                            @endif
                        </div>
                        
                        {{-- Active indicator --}}
                        @if($isActive)
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-{{ $primaryColor }}-600 dark:text-{{ $primaryColor }}-400" fill="currentColor" viewBox="0 0 20 20">
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