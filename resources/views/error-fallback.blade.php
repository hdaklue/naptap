@props([
    'error',
    'tabId'
])

<div class="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400 dark:text-red-300" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Error Loading Tab Content</h3>
            <div class="mt-2 text-sm text-red-700 dark:text-red-400">
                <p>{{ $error }}</p>
            </div>
            <div class="mt-4">
                <div class="-mx-2 -my-1.5 flex">
                    <button
                        type="button"
                        @click="$wire.refreshTab('{{ $tabId }}')"
                        class="rounded-md bg-red-50 dark:bg-red-900/40 px-2 py-1.5 text-sm font-medium text-red-800 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/60 focus:outline-none focus:ring-2 focus:ring-red-600 dark:focus:ring-red-400 focus:ring-offset-2 focus:ring-offset-red-50 dark:focus:ring-offset-gray-800"
                    >
                        Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>