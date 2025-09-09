@props([
    'tab',
    'active' => false,
    'navigationAttributes' => [],
    'layoutManager' => null,
    'accessibilityManager' => null,
    'position' => 1,
    'total' => 1,
])

@php
    $navMode = $navigationAttributes['data-navigation-mode'] ?? 'spa';
    $isNavigateMode = $navMode === 'navigate';
    $isReloadMode = $navMode === 'reload';

    // Get themed classes from layout manager
    $buttonClasses = $layoutManager
        ? $layoutManager->getTabButtonClasses($active, $tab->isDisabled())
        : ($active
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300') .
            ($tab->isDisabled() ? ' opacity-50 cursor-not-allowed' : ' cursor-pointer') .
            ' whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';

    // Get accessibility attributes
    $accessibilityAttributes = $accessibilityManager
        ? $accessibilityManager->getTabAttributes($tab, $active, $position, $total)
        : [
            'role' => 'tab',
            'aria-selected' => $active ? 'true' : 'false',
            'aria-controls' => 'tabpanel-' . $tab->getId(),
            'tabindex' => $active ? '0' : '-1',
        ];

    if ($tab->isDisabled()) {
        $accessibilityAttributes['aria-disabled'] = 'true';
    }
@endphp

@if ($isNavigateMode || $isReloadMode)
    {{-- For navigate/reload modes, use a link instead of button --}}
    <a @if ($navigationAttributes['href'] ?? false) href="{{ $navigationAttributes['href'] }}" @endif
        @if ($isNavigateMode && isset($navigationAttributes['wire:navigate'])) wire:navigate @endif
        @foreach ($navigationAttributes as $attr => $value)
            @if (is_bool($value))
                @if ($value) {{ $attr }} @endif
            @else
                {{ $attr }}="{{ $value }}"
            @endif @endforeach
        class="{{ $buttonClasses }} inline-flex items-center space-x-2 no-underline"
        @foreach ($accessibilityAttributes as $attr => $value)
            {{ $attr }}="{{ $value }}" @endforeach
        @if ($tab->isDisabled()) onclick="return false;" style="pointer-events: none;" @endif>
        @if ($tab->getIcon())
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <use href="#icon-{{ $tab->getIcon() }}"></use>
            </svg>
        @endif

        <span>{{ $tab->getLabel() }}</span>

        @if ($tab->getBadge())
            <span
                class="{{ $layoutManager ? $layoutManager->getBadgeClasses($active) : ($active ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-300') . ' inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm ring-1 ring-inset transition-all duration-200' }}">
                {{ $tab->getBadge() }}
            </span>
        @endif
    </a>
@else
    {{-- SPA mode: use button with wire:click --}}
    <button type="button" wire:click="switchTab('{{ $tab->getId() }}')" wire:loading.attr="disabled"
        wire:target="switchTab"
        @foreach ($navigationAttributes as $attr => $value)
            @if (!str_starts_with($attr, 'wire:') && !str_starts_with($attr, 'href'))
                @if (is_bool($value))
                    @if ($value) {{ $attr }} @endif
                @else
                    {{ $attr }}="{{ $value }}"
                @endif
            @endif @endforeach
        class="{{ $buttonClasses }}"
        @foreach ($accessibilityAttributes as $attr => $value)
            {{ $attr }}="{{ $value }}" @endforeach
        @if ($tab->isDisabled()) disabled @endif>
        <div class="flex items-center space-x-2">
            {{-- Loading spinner - only show when this specific tab is being loaded --}}
            <div wire:loading wire:target="switchTab('{{ $tab->getId() }}')" class="animate-spin">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            {{-- Tab icon - hide when loading --}}
            <div wire:loading.remove wire:target="switchTab('{{ $tab->getId() }}')">
                @if ($tab->getIcon())
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <use href="#icon-{{ $tab->getIcon() }}"></use>
                    </svg>
                @endif
            </div>

            <span>{{ $tab->getLabel() }}</span>

            @if ($tab->getBadge())
                <span
                    class="{{ $layoutManager ? $layoutManager->getBadgeClasses($active) : ($active ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-300') . ' inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm ring-1 ring-inset transition-all duration-200' }}">
                    {{ $tab->getBadge() }}
                </span>
            @endif
        </div>
    </button>
@endif
