<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\Livewire;

use Hdaklue\NapTab\Services\NapTabConfig;
use Hdaklue\NapTab\Services\TabsAccessibilityManager;
use Hdaklue\NapTab\Services\TabsHookManager;
use Hdaklue\NapTab\Services\TabsLayoutManager;
use Hdaklue\NapTab\Services\TabsNavigationManager;
use Hdaklue\NapTab\UI\Tab;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

/**
 * NapTab - Main Livewire tabs component with URL-based state management
 *
 * @property-read string $activeTab Current active tab ID
 * @property-read \Illuminate\Support\Collection<string, \Hdaklue\NapTab\UI\Tab> $tabs Available tabs collection
 */
abstract class NapTab extends Component
{
    public string $activeTab = '';
    public bool $wireNavigate = true; // Optional wire:navigate setup

    /** @var array<string, bool> */
    protected array $loadedTabs = [];
    /** @var array<string, string> */
    protected array $tabErrors = [];
    protected TabsNavigationManager $navigationManager;
    protected TabsHookManager $hookManager;
    protected TabsLayoutManager $layoutManager;
    protected TabsAccessibilityManager $accessibilityManager;
    protected NapTabConfig $config;

    /**
     * @return array<\Hdaklue\NapTab\UI\Tab>
     */
    abstract protected function tabs(): array;

    /**
     * Auto-detect base route from current request
     * Uses current route with {activeTab?} parameter
     */
    public function baseRoute(): string|null
    {
        if (!$this->config->isRoutable()) {
            return null;
        }

        $currentRoute = request()->route();
        if (!$currentRoute) {
            return null;
        }

        // Check if route has activeTab parameter (required for routing)
        $parameters = $currentRoute->parameterNames();
        if (!in_array('activeTab', $parameters)) {
            return null;
        }

        // Build base URL without the activeTab parameter
        $routeName = $currentRoute->getName();
        if (!$routeName) {
            // Handle edge case: no route name - try to build URL from current URI
            $currentUri = request()->getPathInfo();
            $baseUri = preg_replace('/\/[^\/]*$/', '', $currentUri);
            return $baseUri ?: null;
        }

        // Get all route parameters except activeTab
        $routeParams = $currentRoute->parameters();
        unset($routeParams['activeTab']);

        try {
            return route($routeName, $routeParams);
        } catch (\Exception $e) {
            // Fallback: try to build URL from current URI
            $currentUri = request()->getPathInfo();
            $baseUri = preg_replace('/\/[^\/]*$/', '', $currentUri);
            return $baseUri ?: null;
        }
    }

    public function boot(): void
    {
        $this->navigationManager = app(TabsNavigationManager::class);
        $this->hookManager = app(TabsHookManager::class);
        $this->layoutManager = app(TabsLayoutManager::class);
        $this->accessibilityManager = app(TabsAccessibilityManager::class);
        $this->config = app('naptab.config');
    }

    /**
     * Get the NapTab configuration instance
     */
    public function config(): NapTabConfig
    {
        return $this->config;
    }

    public function mount(string|null $activeTab = null): void
    {
        $tabs = $this->getTabsCollection();
        $componentId = $this->getId();

        // Dispatch global init event (optional) and log
        $initContext = [
            'component_id' => $componentId,
            'tabs_count' => $tabs->count(),
            'wire_navigate' => $this->wireNavigate,
        ];
        $this->hookManager->dispatchEvent('init', $initContext);
        $this->hookManager->logHookExecution('init', $initContext);
        
        // Dispatch window event using Livewire 3's js() method
        $this->js("
            window.dispatchEvent(new CustomEvent('tabs:init', {
                detail: " . json_encode($initContext) . "
            }));
        ");

        // Use the route parameter directly for active tab
        $resolvedActiveTab = $activeTab;

        // Validate and set active tab with authorization check
        if ($resolvedActiveTab && $tabs->has($resolvedActiveTab)) {
            $tab = $tabs->get($resolvedActiveTab);

            if ($tab->canAccess()) {
                $this->activeTab = $resolvedActiveTab;
            } else {
                $this->addError('tab', 'Access denied to this tab');
                // Fall back to first accessible tab
                $resolvedActiveTab = null;
            }
        }

        if (!$resolvedActiveTab || !$this->activeTab) {
            // Default to first enabled, visible, and authorized tab
            foreach ($tabs as $tab) {
                if ($tab->canAccess()) {
                    $this->activeTab = $tab->getId();
                    break;
                }
            }
        }

        // No redirect needed - let the route handle the URL

        // Mark active tab as loaded since it's being rendered
        if ($this->activeTab) {
            $this->markTabAsLoaded($this->activeTab);
        }
    }

    /**
     * Get the current active tab, checking route parameter if routable
     */
    protected function getCurrentActiveTab(): string
    {
        if ($this->config->isRoutable()) {
            $currentRoute = request()->route();
            
            if ($currentRoute && in_array('activeTab', $currentRoute->parameterNames())) {
                $routeActiveTab = $currentRoute->parameter('activeTab');
                
                if ($routeActiveTab) {
                    $tabs = $this->getTabsCollection();
                    
                    // Validate the route tab
                    if ($tabs->has($routeActiveTab)) {
                        $tab = $tabs->get($routeActiveTab);
                        
                        if ($tab->canAccess()) {
                            return $routeActiveTab;
                        }
                    }
                }
            }
        }
        
        return $this->activeTab;
    }

    /**
     * Handle component synchronization after route navigation
     * This is crucial for wire:navigate and browser back button support
     */
    public function hydrate(): void
    {
        // When using routable, ensure activeTab is synchronized with route parameter
        if ($this->config->isRoutable()) {
            $routeActiveTab = $this->getCurrentActiveTab();
            
            if ($routeActiveTab !== $this->activeTab) {
                $oldTab = $this->activeTab;
                $this->activeTab = $routeActiveTab;
                
                // Ensure the new tab is loaded for immediate display
                $this->markTabAsLoaded($routeActiveTab);
                
                // Dispatch tab changed event for frontend synchronization
                $this->dispatch('tab-changed', [
                    'oldTab' => $oldTab,
                    'newTab' => $routeActiveTab,
                    'source' => 'navigation' // Indicate this came from browser navigation
                ]);
                
                // Dispatch window event for browser integration
                $this->js("
                    window.dispatchEvent(new CustomEvent('tab:navigationChanged', {
                        detail: {
                            oldTab: " . json_encode($oldTab) . ",
                            newTab: " . json_encode($routeActiveTab) . ",
                            source: 'wire-navigate'
                        }
                    }));
                ");
            }
        }
    }

    public function switchTab(string $tabId): void
    {
        // Basic validation - ensure tab ID is safe
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tabId) || strlen($tabId) > 50) {
            $this->addError('tab', 'Invalid tab identifier');
            return;
        }

        $tabs = $this->getTabsCollection();

        if (!$tabs->has($tabId)) {
            $this->addError('tab', "Tab '{$tabId}' not found");
            return;
        }

        $tab = $tabs->get($tabId);

        // Authorization check
        if (!$tab->canAccess()) {
            $this->addError('tab', 'Access denied to this tab');
            return;
        }

        // Execute onSwitch hook if defined
        if ($tab->hasOnSwitchHook()) {
            $switchResult = $tab->executeOnSwitch($this->activeTab, $tabId, [
                'component_id' => $this->getId(),
            ]);

            // Allow hook to prevent switching
            if (is_array($switchResult) && isset($switchResult['cancel']) && $switchResult['cancel']) {
                $this->addError('tab', $switchResult['message'] ?? 'Tab switch cancelled');
                return;
            }
        }

        $oldTabId = $this->activeTab;
        $this->activeTab = $tabId;
        $this->markTabAsLoaded($tabId);

        // Dispatch tab changed event for mobile auto-scroll
        $this->dispatch('tab-changed', [
            'oldTab' => $oldTabId,
            'newTab' => $tabId
        ]);

        // For routable tabs, URL navigation is handled by wire:navigate in the template
        // Only redirect programmatically if this is not a routable navigation
        $shouldRedirect = false;
        
        if ($this->config->isRoutable()) {
            $currentRoute = request()->route();
            
            // Check if this is a programmatic tab switch (not from URL navigation)
            if ($currentRoute && in_array('activeTab', $currentRoute->parameterNames())) {
                $currentActiveTab = $currentRoute->parameter('activeTab');
                
                // Only redirect if we're switching to a different tab programmatically
                if ($currentActiveTab !== $tabId) {
                    $shouldRedirect = true;
                }
            }
        }
        
        if ($shouldRedirect) {
            $currentRoute = request()->route();
            $routeName = $currentRoute->getName();
            $routeParams = $currentRoute->parameters();
            $routeParams['activeTab'] = $tabId;
            
            try {
                if ($routeName) {
                    $url = route($routeName, $routeParams);
                    $this->redirect($url, navigate: true);
                } else {
                    // Handle edge case: no route name - build URL from current URI
                    $currentUri = request()->getPathInfo();
                    $baseUri = preg_replace('/\/[^\/]*$/', '', $currentUri);
                    $url = $baseUri . '/' . $tabId;
                    $this->redirect($url, navigate: true);
                }
            } catch (\Exception $e) {
                // Fallback to SPA mode if route building fails
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function loadTabContent(string $tabId): array
    {
        try {
            // Validate tab ID format for security
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tabId) || strlen($tabId) > 50) {
                throw new \Exception('Invalid tab identifier format');
            }

            $tabs = $this->getTabsCollection();

            if (!$tabs->has($tabId)) {
                throw new \Exception("Tab '{$tabId}' not found");
            }

            $tab = $tabs->get($tabId);

            if ($tab->isDisabled()) {
                throw new \Exception("Tab '{$tabId}' is disabled");
            }

            // Execute beforeLoad hook if defined
            if ($tab->hasBeforeLoadHook()) {
                $beforeLoadResult = $tab->executeBeforeLoad(['tabId' => $tabId]);
                if (is_array($beforeLoadResult) && isset($beforeLoadResult['cancel']) && $beforeLoadResult['cancel']) {
                    throw new \Exception($beforeLoadResult['message'] ?? 'Tab loading cancelled by beforeLoad hook');
                }
            }

            // Dispatch global before load event (optional) and log
            $beforeLoadContext = [
                'tab_id' => $tabId,
                'tab_label' => $tab->getLabel(),
            ];
            $this->hookManager->dispatchEvent('before_load', $beforeLoadContext);
            $this->hookManager->logHookExecution('before_load', $beforeLoadContext);

            $content = $this->renderTabContent($tab);

            // Execute afterLoad hook if defined
            if ($tab->hasAfterLoadHook()) {
                $afterLoadResult = $tab->executeAfterLoad($content, ['tabId' => $tabId]);
                // Allow hook to modify content
                if (is_array($afterLoadResult) && isset($afterLoadResult['modifiedContent'])) {
                    $content = $afterLoadResult['modifiedContent'];
                }
            }

            // Dispatch global after load event (optional) and log
            $afterLoadContext = [
                'tab_id' => $tabId,
                'content_length' => strlen($content),
            ];
            $this->hookManager->dispatchEvent('after_load', $afterLoadContext);
            $this->hookManager->logHookExecution('after_load', $afterLoadContext);

            $this->markTabAsLoaded($tabId);
            unset($this->tabErrors[$tabId]);

            return [
                'success' => true,
                'tabId' => $tabId,
                'content' => $content,
                'hasLivewire' => $tab->hasLivewireComponent(),
            ];
        } catch (\Exception $e) {
            $tabs = $this->getTabsCollection();
            $tab = $tabs->get($tabId);

            // Execute onError hook if defined
            if ($tab && $tab->hasOnErrorHook()) {
                $errorResult = $tab->executeOnError($e, ['tabId' => $tabId]);
                // Allow hook to provide fallback content
                if (is_array($errorResult) && isset($errorResult['fallbackContent'])) {
                    return [
                        'success' => true,
                        'tabId' => $tabId,
                        'content' => $errorResult['fallbackContent'],
                        'hasLivewire' => false,
                    ];
                }
            }

            $this->tabErrors[$tabId] = $e->getMessage();

            return [
                'success' => false,
                'tabId' => $tabId,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function refreshTab(string $tabId): void
    {
        // Validate tab ID format for security
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tabId) || strlen($tabId) > 50) {
            $this->addError('tab', 'Invalid tab identifier format');
            return;
        }

        unset($this->loadedTabs[$tabId]);
        unset($this->tabErrors[$tabId]);

        if ($this->activeTab === $tabId) {
            $this->markTabAsLoaded($tabId);
        }

        $this->dispatch('tab:refreshed', ['tabId' => $tabId]);
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Hdaklue\NapTab\UI\Tab>
     */
    protected function getTabsCollection(): Collection
    {
        return collect($this->tabs())->filter(fn(Tab $tab) => $tab->isVisible())->keyBy(fn(Tab $tab) => $tab->getId());
    }

    protected function markTabAsLoaded(string $tabId): void
    {
        $this->loadedTabs[$tabId] = true;
    }

    protected function isTabLoaded(string $tabId): bool
    {
        return isset($this->loadedTabs[$tabId]);
    }

    protected function hasTabError(string $tabId): bool
    {
        return isset($this->tabErrors[$tabId]);
    }

    protected function getTabError(string $tabId): string|null
    {
        return $this->tabErrors[$tabId] ?? null;
    }

    protected function renderTabContent(Tab $tab): string
    {
        if ($tab->hasContent()) {
            return $tab->renderContent();
        }

        if ($tab->hasLivewireComponent()) {
            return view('naptab::livewire-placeholder', [
                'component' => $tab->getLivewireComponent(),
                'params' => $tab->getLivewireParams(),
                'tabId' => $tab->getId(),
            ])->render();
        }

        return '<p class="text-gray-500">No content available for this tab.</p>';
    }

    public function getActiveTabProperty(): string
    {
        return $this->activeTab;
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Hdaklue\NapTab\UI\Tab>
     */
    public function getTabsProperty(): Collection
    {
        return $this->getTabsCollection();
    }

    /**
     * Get navigation attributes for a specific tab
     */
    /**
     * @return array<string, mixed>
     */
    public function getTabNavigationAttributes(string $tabId): array
    {
        return $this->navigationManager->getNavigationAttributes($tabId);
    }

    /**
     * Get JavaScript code for navigation and accessibility
     * Hooks are handled via Livewire events, not JavaScript
     */
    public function getNavigationJavaScript(): string
    {
        $navigationJs = $this->navigationManager->generateNavigationJavaScript();
        $accessibilityJs = $this->accessibilityManager->generateFocusManagementScript();

        return $navigationJs . "\n" . $accessibilityJs;
    }

    public function render(): View
    {
        $config = $this->config->toArray();
        $currentActiveTab = $this->getCurrentActiveTab();
        
        // Update activeTab if it changed via route
        if ($currentActiveTab !== $this->activeTab) {
            $this->activeTab = $currentActiveTab;
            $this->markTabAsLoaded($currentActiveTab);
        }
        
        return view('naptab::index', [
            'tabs' => $this->getTabsCollection(),
            'activeTab' => $this->activeTab,
            'loadedTabs' => $this->loadedTabs,
            'tabErrors' => $this->tabErrors,
            'navigationScript' => $this->getNavigationJavaScript(),
            'styles' => $config['styles'],
            'spacing' => $config['styles']['spacing'],
        ]);
    }

    // Livewire event listeners for browser integration
    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'browser:popstate' => 'handleBrowserNavigation',
            'tab:preload' => 'preloadTab',
        ];
    }

    /**
     * Handle browser navigation events (for cases where wire:navigate isn't used)
     * @param array<string, mixed> $data
     */
    public function handleBrowserNavigation(array $data): void
    {
        $tabFromUrl = $data['tab'] ?? '';

        if ($tabFromUrl && $this->getTabsCollection()->has($tabFromUrl)) {
            $tab = $this->getTabsCollection()->get($tabFromUrl);
            
            // Check authorization before switching
            if ($tab->canAccess()) {
                // For wire:navigate enabled components, prefer URL redirect
                if ($this->wireNavigate && $this->config->isRoutable()) {
                    $currentRoute = request()->route();
                    if ($currentRoute) {
                        $routeParams = $currentRoute->parameters();
                        $routeParams['activeTab'] = $tabFromUrl;
                        
                        try {
                            $url = route($currentRoute->getName(), $routeParams);
                            $this->redirect($url, navigate: true);
                            return;
                        } catch (\Exception $e) {
                            // Fall back to direct tab switching
                        }
                    }
                }
                
                // Direct tab switching as fallback
                $this->switchTab($tabFromUrl);
            }
        }
    }

    public function preloadTab(string $tabId): void
    {
        if (!$this->isTabLoaded($tabId)) {
            $this->loadTabContent($tabId);
        }
    }
}
