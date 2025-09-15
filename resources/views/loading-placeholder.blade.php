@props([])

<div class="flex items-center justify-center py-12">
    <div class="text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-{{ app('naptab.config')->toArray()['colors']['primary'] }}-500 dark:border-{{ app('naptab.config')->toArray()['colors']['primary'] }}-400 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Loading content...</p>
    </div>
</div>