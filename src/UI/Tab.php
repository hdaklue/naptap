<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\UI;

use Closure;
use Hdaklue\NapTab\Services\NapTabConfig;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Filament-style Tab configuration class with fluent API
 *
 * @property-read string $id
 * @property-read string $label  
 * @property-read string|null $icon
 * @property-read string|null $badge
 * @property-read bool $disabled
 * @property-read \Closure|\Illuminate\Contracts\Support\Htmlable|null $content
 * @property-read string|null $livewireComponent
 * @property-read array<string, mixed> $livewireParams
 */
class Tab extends Component
{
    protected string $id;
    protected \Closure|string $label;
    protected \Closure|string|null $icon = null;
    protected \Closure|string|null $badge = null;
    protected \Closure|bool $disabled = false;
    protected \Closure|\Illuminate\Contracts\Support\Htmlable|null $content = null;
    protected \Closure|string|null $livewireComponent = null;
    /** @var \Closure|array<string, mixed> */
    protected \Closure|array $livewireParams = [];
    protected \Closure|bool|null $visibility = null;
    protected \Closure|null $beforeLoadHook = null;
    protected \Closure|null $afterLoadHook = null;
    protected \Closure|null $onErrorHook = null;
    protected \Closure|null $onSwitchHook = null;

    public function __construct(string $id)
    {
        if (empty(trim($id))) {
            throw new InvalidArgumentException('Tab ID cannot be empty');
        }

        // Enhanced validation: only allow safe characters for tab IDs
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id) || strlen($id) > 50) {
            throw new InvalidArgumentException('Tab ID must contain only alphanumeric characters, hyphens, and underscores, and be no longer than 50 characters');
        }

        $this->id = $id;
        $this->label = ucfirst(str_replace(['-', '_'], ' ', $id));
    }

    public static function make(string $id): self
    {
        return new self($id);
    }

    public function label(\Closure|string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function icon(\Closure|string|null $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function badge(\Closure|string|null $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    public function disabled(\Closure|bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }


    public function visible(\Closure|bool $visible): self
    {
        $this->visibility = $visible;
        return $this;
    }

    public function content(\Closure|\Illuminate\Contracts\Support\Htmlable $content): self
    {
        if ($this->livewireComponent !== null) {
            throw new InvalidArgumentException('Cannot set both content closure and Livewire component');
        }

        $this->content = $content;
        return $this;
    }

    /**
     * @param \Closure|string $component
     * @param \Closure|array<string, mixed> $params
     */
    public function livewire(\Closure|string $component, \Closure|array $params = []): self
    {
        if ($this->content !== null) {
            throw new InvalidArgumentException('Cannot set both content closure and Livewire component');
        }

        $this->livewireComponent = $component;
        $this->livewireParams = $params;
        return $this;
    }

    public function beforeLoad(\Closure $hook): self
    {
        $this->beforeLoadHook = $hook;
        return $this;
    }

    public function afterLoad(\Closure $hook): self
    {
        $this->afterLoadHook = $hook;
        return $this;
    }

    public function onError(\Closure $hook): self
    {
        $this->onErrorHook = $hook;
        return $this;
    }

    public function onSwitch(\Closure $hook): self
    {
        $this->onSwitchHook = $hook;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->evaluate($this->label);
    }

    public function getIcon(): null|string
    {
        return $this->evaluate($this->icon);
    }

    public function getBadge(): null|string
    {
        return $this->evaluate($this->badge);
    }

    public function isDisabled(): bool
    {
        return $this->evaluate($this->disabled);
    }

    public function canAccess(): bool
    {
        return $this->isVisible();
    }

    public function isVisible(): bool
    {
        if ($this->visibility !== null) {
            $result = $this->evaluate($this->visibility);
            return is_bool($result) ? $result : true;
        }

        return true; // Default: visible
    }


    public function getContent(): \Closure|null
    {
        return $this->content;
    }

    public function getLivewireComponent(): null|string
    {
        return $this->evaluate($this->livewireComponent);
    }

    /**
     * @return array<string, mixed>
     */
    public function getLivewireParams(): array
    {
        return $this->evaluate($this->livewireParams);
    }

    public function hasContent(): bool
    {
        return $this->content !== null;
    }

    public function hasLivewireComponent(): bool
    {
        return $this->livewireComponent !== null;
    }

    public function hasBeforeLoadHook(): bool
    {
        return $this->beforeLoadHook !== null;
    }

    public function hasAfterLoadHook(): bool
    {
        return $this->afterLoadHook !== null;
    }

    public function hasOnErrorHook(): bool
    {
        return $this->onErrorHook !== null;
    }

    public function hasOnSwitchHook(): bool
    {
        return $this->onSwitchHook !== null;
    }

    public function executeBeforeLoad(array $context = []): mixed
    {
        // Dispatch window event using Livewire 3's js() method
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:beforeLoad', {
                detail: {
                    tabId: " . json_encode($this->id) . ",
                    context: " . json_encode($context) . "
                }
            }));
        ");

        $result = $this->beforeLoadHook ? ($this->beforeLoadHook)($this, $context) : null;
        
        // Log the hook execution for debugging
        if (static::config()->get('debug', false)) {
            \Illuminate\Support\Facades\Log::debug("Tab Hook Executed: beforeLoad", [
                'tabId' => $this->id,
                'context' => $context,
                'result' => $result
            ]);
        }

        return $result;
    }

    public function executeAfterLoad(string $content, array $context = []): mixed
    {
        // Dispatch window event using Livewire 3's js() method
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:afterLoad', {
                detail: {
                    tabId: " . json_encode($this->id) . ",
                    contentLength: " . strlen($content) . ",
                    context: " . json_encode($context) . "
                }
            }));
        ");

        $result = $this->afterLoadHook ? ($this->afterLoadHook)($this, $content, $context) : null;
        
        // Log the hook execution for debugging
        if (static::config()->get('debug', false)) {
            \Illuminate\Support\Facades\Log::debug("Tab Hook Executed: afterLoad", [
                'tabId' => $this->id,
                'contentLength' => strlen($content),
                'context' => $context,
                'result' => $result
            ]);
        }

        return $result;
    }

    public function executeOnError(\Exception $error, array $context = []): mixed
    {
        // Dispatch window event using Livewire 3's js() method
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:error', {
                detail: {
                    tabId: " . json_encode($this->id) . ",
                    error: " . json_encode($error->getMessage()) . ",
                    context: " . json_encode($context) . "
                }
            }));
        ");

        $result = $this->onErrorHook ? ($this->onErrorHook)($this, $error, $context) : null;
        
        // Log the hook execution for debugging
        if (static::config()->get('debug', false)) {
            \Illuminate\Support\Facades\Log::debug("Tab Hook Executed: onError", [
                'tabId' => $this->id,
                'error' => $error->getMessage(),
                'context' => $context,
                'result' => $result
            ]);
        }

        return $result;
    }

    public function executeOnSwitch(string $fromTabId, string $toTabId, array $context = []): mixed
    {
        // Dispatch window event using Livewire 3's js() method
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:switch', {
                detail: {
                    fromTabId: " . json_encode($fromTabId) . ",
                    toTabId: " . json_encode($toTabId) . ",
                    context: " . json_encode($context) . "
                }
            }));
        ");

        $result = $this->onSwitchHook ? ($this->onSwitchHook)($this, $fromTabId, $toTabId, $context) : null;
        
        // Log the hook execution for debugging
        if (static::config()->get('debug', false)) {
            \Illuminate\Support\Facades\Log::debug("Tab Hook Executed: onSwitch", [
                'tabId' => $this->id,
                'fromTabId' => $fromTabId,
                'toTabId' => $toTabId,
                'context' => $context,
                'result' => $result
            ]);
        }

        return $result;
    }

    public function renderContent(): string
    {
        if ($this->content === null) {
            return '';
        }

        // Handle Htmlable objects
        if ($this->content instanceof Htmlable) {
            return $this->content->toHtml();
        }

        // Handle Closure callables
        if ($this->content instanceof Closure) {
            $result = ($this->content)();
            return is_string($result) ? $result : '';
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'badge' => $this->getBadge(),
            'disabled' => $this->isDisabled(),
            'hasContent' => $this->hasContent(),
            'hasLivewireComponent' => $this->hasLivewireComponent(),
            'livewireComponent' => $this->getLivewireComponent(),
            'livewireParams' => $this->getLivewireParams(),
        ];
    }

    /**
     * Get the NapTab configuration instance
     */
    public static function config(): NapTabConfig
    {
        return app('naptab.config');
    }

    /**
     * Livewire Component methods
     */
    public function mount(string $id): void
    {
        $this->id = $id;
    }

    public function render()
    {
        // Tab is a configuration class, not rendered directly
        return null;
    }

    /**
     * Dispatch Livewire events for this tab
     */
    public function dispatchTabEvent(string $event, array $data = []): void
    {
        $this->dispatch("tab:{$event}", array_merge(['tabId' => $this->id], $data));
    }


    /**
     * Redirect with Livewire navigation for this tab
     */
    public function navigateToTab(string $url): void
    {
        // Validate URL to prevent open redirect vulnerabilities
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/')) {
            throw new InvalidArgumentException('Invalid URL provided for navigation');
        }

        // Prevent external redirects for security
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $currentHost = request()->getHost();
            
            if (isset($parsedUrl['host']) && $parsedUrl['host'] !== $currentHost) {
                throw new InvalidArgumentException('External redirects are not allowed');
            }
        }

        $this->redirect($url, navigate: true);
    }

    /**
     * Evaluate a property value - if it's a closure, call it, otherwise return as-is
     * This is the central evaluation method inspired by Filament
     */
    protected function evaluate(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return $value();
        }

        return $value;
    }
}
