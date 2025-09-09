{{-- RTL Support Enhancement for NapTab --}}
<style>
/* RTL-specific overrides for NapTab components */
[dir="rtl"] .tab-hover-gradient::before {
    background: linear-gradient(-90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
}

[dir="rtl"] .tab-active-premium::after {
    background: linear-gradient(-90deg, transparent, currentColor, transparent);
}

/* Ensure proper text alignment in RTL */
[dir="rtl"] .nap-tab-container {
    text-align: start;
}

[dir="rtl"] .nap-tab-content {
    text-align: start;
}

/* RTL-specific animations */
@media (prefers-color-scheme: dark) {
    [dir="rtl"] .tab-hover-gradient::before {
        background: linear-gradient(-90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    }
}

/* Fix any potential overflow issues in RTL */
[dir="rtl"] .overflow-x-auto {
    direction: rtl;
}

[dir="rtl"] .overflow-x-auto > * {
    direction: ltr;
}
</style>

{{-- Helper to detect RTL --}}
@php
    $isRtl = app()->getLocale() === 'ar' || 
             app()->getLocale() === 'he' || 
             app()->getLocale() === 'fa' ||
             request()->header('Content-Language') === 'ar' ||
             config('app.direction', 'ltr') === 'rtl';
@endphp

@if($isRtl)
    <script>
        // Automatically set dir attribute if RTL is detected
        if (!document.documentElement.hasAttribute('dir')) {
            document.documentElement.setAttribute('dir', 'rtl');
        }
        
        // Add RTL class to body for additional styling hooks
        document.body.classList.add('nap-tab-rtl');
    </script>
@endif