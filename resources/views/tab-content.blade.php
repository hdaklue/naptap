@props([
    'tab',
    'active' => false,
    'loaded' => false,
    'error' => null
])

@php
    $config = app('naptab.config')->toArray();
    $animationType = $config['styles']['animations']['content_animation'] ?? 'fade';
    
    // Get animation directives based on type
    $animationDirectives = match($animationType) {
        'none' => [],
        'fade' => [
            'x-transition:enter' => 'transition ease-out duration-200',
            'x-transition:enter-start' => 'opacity-0',
            'x-transition:enter-end' => 'opacity-100',
        ],
        'scale' => [
            'x-transition:enter' => 'transition ease-out duration-200',
            'x-transition:enter-start' => 'opacity-0 transform scale-95',
            'x-transition:enter-end' => 'opacity-100 transform scale-100',
        ],
        'slide' => [
            'x-transition:enter' => 'transition ease-out duration-200',
            'x-transition:enter-start' => 'opacity-0 transform translate-x-4',
            'x-transition:enter-end' => 'opacity-100 transform translate-x-0',
        ],
        default => [
            'x-transition:enter' => 'transition ease-out duration-200',
            'x-transition:enter-start' => 'opacity-0',
            'x-transition:enter-end' => 'opacity-100',
        ]
    };
@endphp

<div
    wire:key="tab-content-{{ $tab->getId() }}"
    @foreach($animationDirectives as $directive => $value)
        {{ $directive }}="{{ $value }}"
    @endforeach
    class="focus:outline-none"
    id="tab-panel-{{ $tab->getId() }}"
    role="tabpanel"
    aria-labelledby="tab-{{ $tab->getId() }}"
    tabindex="0"
>
    {{-- Error State --}}
    @if($error)
        @include('naptab::error-fallback', [
            'error' => $error,
            'tabId' => $tab->getId()
        ])
    {{-- Loading State --}}
    @elseif(!$loaded && $active)
        @include('naptab::loading-placeholder')
    {{-- Content State --}}
    @else
        <div wire:key="tab-inner-{{ $tab->getId() }}" x-ref="tab-content-{{ $tab->getId() }}" class="flex flex-col min-h-full">
            {{-- Before Content (contained) --}}
            @if($tab->hasBeforeContent())
                <div class="mb-4">
                    {!! $tab->renderBeforeContent() !!}
                </div>
            @endif

            {{-- Main Content --}}
            <div class="flex-1">
                @if($tab->hasContent())
                    {{-- Callable Content --}}
                    <div class="prose max-w-none dark:prose-invert">
                        {!! $tab->renderContent() !!}
                    </div>
                @elseif($tab->hasLivewireComponent())
                    {{-- Livewire Component --}}
                    <div>
                        @if($loaded)
                            @livewire($tab->getLivewireComponent(), $tab->getLivewireParams(), key('tab-livewire-' . $tab->getId()))
                        @else
                            @include('naptab::livewire-placeholder', [
                                'component' => $tab->getLivewireComponent(),
                                'params' => $tab->getLivewireParams(),
                                'tabId' => $tab->getId()
                            ])
                        @endif
                    </div>
                @else
                    {{-- Empty State --}}
                    @include('naptab::empty-state', ['tabId' => $tab->getId()])
                @endif
            </div>

            {{-- After Content (contained) --}}
            @if($tab->hasAfterContent())
                <div class="mt-4">
                    {!! $tab->renderAfterContent() !!}
                </div>
            @endif
        </div>
    @endif
</div>