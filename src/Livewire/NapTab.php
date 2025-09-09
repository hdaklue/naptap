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
 * @property-read Collection<Tab> $tabs Available tabs collection
 */
abstract class NapTab extends Component
{
    public string $activeTab = '';
    public bool $wireNavigate = true; // Optional wire:navigate setup

    protected array $loadedTabs = [];
    protected array $tabErrors = [];
    protected TabsNavigationManager $navigationManager;
    protected TabsHookManager $hookManager;
    protected TabsLayoutManager $layoutManager;
    protected TabsAccessibilityManager $accessibilityManager;
    protected NapTabConfig $config;

    abstract protected function tabs(): array;

    /**
     * Auto-detect base route from current request
     * Uses current route with {activeTab?} parameter
     */
    public function baseRoute(): null|string
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

    public function mount(null|string $activeTab = null): void
    {
        $tabs = $this->getTabsCollection();
        $componentId = $this->getId();

        // Dispatch global init event (optional)
        $this->hookManager->dispatchEvent('init', [
            'component_id' => $componentId,
            'tabs_count' => $tabs->count(),
            'wire_navigate' => $this->wireNavigate,
        ]);

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

        // Handle navigation based on routing configuration
        if ($this->config->isRoutable()) {
            $currentRoute = request()->route();
            
            if ($currentRoute && in_array('activeTab', $currentRoute->parameterNames())) {
                // Build URL using current route with activeTab parameter
                $routeName = $currentRoute->getName();
                $routeParams = $currentRoute->parameters();
                $routeParams['activeTab'] = $tabId;
                
                try {
                    if ($routeName) {
                        $url = route($routeName, $routeParams);
                    } else {
                        // Handle edge case: no route name - build URL from current URI
                        $currentUri = request()->getPathInfo();
                        $baseUri = preg_replace('/\/[^\/]*$/', '', $currentUri);
                        $url = $baseUri . '/' . $tabId;
                    }
                    $this->redirect($url, navigate: true);
                } catch (\Exception $e) {
                    // Fallback to SPA mode if route building fails
                }
            }
        }
        // If not routable or route building failed, stay in SPA mode
    }

    public function loadTabContent(string $tabId): array
    {
        try {
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

            // Dispatch global before load event (optional)
            $this->hookManager->dispatchEvent('before_load', [
                'tab_id' => $tabId,
                'tab_label' => $tab->getLabel(),
            ]);

            $content = $this->renderTabContent($tab);

            // Execute afterLoad hook if defined
            if ($tab->hasAfterLoadHook()) {
                $afterLoadResult = $tab->executeAfterLoad($content, ['tabId' => $tabId]);
                // Allow hook to modify content
                if (is_array($afterLoadResult) && isset($afterLoadResult['modifiedContent'])) {
                    $content = $afterLoadResult['modifiedContent'];
                }
            }

            // Dispatch global after load event (optional)
            $this->hookManager->dispatchEvent('after_load', [
                'tab_id' => $tabId,
                'content_length' => strlen($content),
            ]);

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
        unset($this->loadedTabs[$tabId]);
        unset($this->tabErrors[$tabId]);

        if ($this->activeTab === $tabId) {
            $this->markTabAsLoaded($tabId);
        }

        $this->dispatch('tab:refreshed', ['tabId' => $tabId]);
    }

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

    protected function getTabError(string $tabId): null|string
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

    public function getTabsProperty(): Collection
    {
        return $this->getTabsCollection();
    }

    /**
     * Get navigation attributes for a specific tab
     */
    public function getTabNavigationAttributes(string $tabId): array
    {
        return $this->navigationManager->getNavigationAttributes($tabId);
    }

    /**
     * Get JavaScript code for navigation, hooks, and accessibility
     */
    public function getNavigationJavaScript(): string
    {
        $navigationJs = $this->navigationManager->generateNavigationJavaScript();
        $hooksJs = $this->hookManager->generateJavaScriptHooks();
        $accessibilityJs = $this->accessibilityManager->generateFocusManagementScript();

        return $navigationJs . "\n" . $hooksJs . "\n" . $accessibilityJs;
    }

    public function render(): View
    {
        $config = $this->config->toArray();
        
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

    // JavaScript hooks for browser integration
    public function getListeners(): array
    {
        return [
            'browser:popstate' => 'handleBrowserNavigation',
            'tab:preload' => 'preloadTab',
        ];
    }

    public function handleBrowserNavigation(array $data): void
    {
        $tabFromUrl = $data['tab'] ?? '';

        if ($tabFromUrl && $this->getTabsCollection()->has($tabFromUrl)) {
            $this->switchTab($tabFromUrl);
        }
    }

    public function preloadTab(string $tabId): void
    {
        if (!$this->isTabLoaded($tabId)) {
            $this->loadTabContent($tabId);
        }
    }
}
