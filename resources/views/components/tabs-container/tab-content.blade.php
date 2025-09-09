@props([
    'tab',
    'active' => false,
    'loaded' => false,
    'error' => null
])

<div
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="focus:outline-none"
    id="tab-panel-{{ $tab->getId() }}"
    role="tabpanel"
    aria-labelledby="tab-{{ $tab->getId() }}"
    tabindex="0"
>
    {{-- Error State --}}
    @if($error)
        <x-tabs-container.error-fallback 
            :error="$error" 
            :tabId="$tab->getId()" 
        />
    {{-- Loading State --}}
    @elseif(!$loaded && $active)
        <x-tabs-container.loading-placeholder />
    {{-- Content State --}}
    @else
        <div x-ref="tab-content-{{ $tab->getId() }}">
            @if($tab->hasContent())
                {{-- Callable Content --}}
                <div class="prose max-w-none dark:prose-invert">
                    {!! $tab->renderContent() !!}
                </div>
            @elseif($tab->hasLivewireComponent())
                {{-- Livewire Component --}}
                <div>
                    @if($loaded)
                        @livewire($tab->getLivewireComponent(), $tab->getLivewireParams(), key($tab->getId()))
                    @else
                        <x-tabs-container.livewire-placeholder 
                            :component="$tab->getLivewireComponent()"
                            :params="$tab->getLivewireParams()"
                            :tabId="$tab->getId()"
                        />
                    @endif
                </div>
            @else
                {{-- Empty State --}}
                <x-tabs-container.empty-state :tabId="$tab->getId()" />
            @endif
        </div>
    @endif
</div>