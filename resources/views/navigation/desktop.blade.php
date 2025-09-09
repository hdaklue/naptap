{{-- Desktop Tab Navigation with Auto-Scroll --}}
<nav class="{{ $spacing['tab_gap'] ?? 'gap-2' }} {{ $spacing['nav_padding'] ?? 'px-1' }} -mb-px flex w-full 
    overflow-x-auto scrollbar-hide scroll-smooth snap-x snap-mandatory"
    aria-label="Tabs" role="tablist" 
    x-data="{ 
        scrollToActive() {
            const activeTab = this.$el.querySelector('[aria-selected=true]');
            if (activeTab) {
                activeTab.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest', 
                    inline: 'center' 
                });
            }
        }
    }"
    x-init="
        $nextTick(() => scrollToActive());
        $wire.on('tab-changed', () => {
            setTimeout(() => scrollToActive(), 100);
        });
        window.addEventListener('resize', () => {
            scrollToActive();
        });
    ">
    
    @include('naptab::navigation.tab-items', ['tabs' => $tabs, 'activeTab' => $activeTab, 'styles' => $styles])
</nav>