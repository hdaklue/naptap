<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\UI;

use Closure;
use Hdaklu\NapTab\Services\NapTabConfig;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Filament-style Tab configuration class with fluent API
 *
 * @property-read string $id
 * @property-read string $label
 * @property-read ?string $icon
 * @property-read ?string $badge
 * @property-read bool $disabled
 * @property-read Closure|Htmlable|null $content
 * @property-read ?string $livewireComponent
 * @property-read array $livewireParams
 */
class Tab extends Component
{
    protected string $id;
    protected Closure|string $label;
    protected Closure|string|null $icon = null;
    protected Closure|string|null $badge = null;
    protected Closure|bool $disabled = false;
    protected Closure|Htmlable|null $content = null;
    protected Closure|string|null $livewireComponent = null;
    protected Closure|array $livewireParams = [];
    protected Closure|bool|null $authorization = null;
    protected Closure|bool|null $visibility = null;
    protected null|Closure $beforeLoadHook = null;
    protected null|Closure $afterLoadHook = null;
    protected null|Closure $onErrorHook = null;
    protected null|Closure $onSwitchHook = null;

    public function __construct(string $id)
    {
        if (empty(trim($id))) {
            throw new InvalidArgumentException('Tab ID cannot be empty');
        }

        $this->id = $id;
        $this->label = ucfirst(str_replace(['-', '_'], ' ', $id));
    }

    public static function make(string $id): self
    {
        return new self($id);
    }

    public function label(Closure|string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function icon(Closure|string|null $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function badge(Closure|string|null $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    public function disabled(Closure|bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function authorizeAccess(Closure|bool $authorization): self
    {
        $this->authorization = $authorization;
        return $this;
    }

    public function visible(Closure|bool $visible): self
    {
        $this->visibility = $visible;
        return $this;
    }

    public function content(Closure|Htmlable $content): self
    {
        if ($this->livewireComponent !== null) {
            throw new InvalidArgumentException('Cannot set both content closure and Livewire component');
        }

        $this->content = $content;
        return $this;
    }

    public function livewire(Closure|string $component, Closure|array $params = []): self
    {
        if ($this->content !== null) {
            throw new InvalidArgumentException('Cannot set both content closure and Livewire component');
        }

        $this->livewireComponent = $component;
        $this->livewireParams = $params;
        return $this;
    }

    public function beforeLoad(Closure $hook): self
    {
        $this->beforeLoadHook = $hook;
        return $this;
    }

    public function afterLoad(Closure $hook): self
    {
        $this->afterLoadHook = $hook;
        return $this;
    }

    public function onError(Closure $hook): self
    {
        $this->onErrorHook = $hook;
        return $this;
    }

    public function onSwitch(Closure $hook): self
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
        return $this->isVisible() && $this->isAuthorized();
    }

    public function isVisible(): bool
    {
        if ($this->visibility !== null) {
            $result = $this->evaluate($this->visibility);
            return is_bool($result) ? $result : true;
        }

        return true; // Default: visible
    }

    public function isAuthorized(): bool
    {
        if ($this->authorization !== null) {
            $result = $this->evaluate($this->authorization);
            return is_bool($result) ? $result : true;
        }

        return true; // Default: authorized
    }

    public function getContent(): null|Closure
    {
        return $this->content;
    }

    public function getLivewireComponent(): null|string
    {
        return $this->evaluate($this->livewireComponent);
    }

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
        // Dispatch JavaScript event
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:beforeLoad', {
                detail: { tabId: '{$this->id}', context: "
        . json_encode($context)
        . ' }
            }))
        ');

        return $this->beforeLoadHook ? ($this->beforeLoadHook)($this, $context) : null;
    }

    public function executeAfterLoad(string $content, array $context = []): mixed
    {
        // Dispatch JavaScript event
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:afterLoad', {
                detail: {
                    tabId: '{$this->id}',
                    contentLength: "
        . strlen($content)
        . ',
                    context: '
        . json_encode($context)
        . '
                }
            }))
        ');

        return $this->afterLoadHook ? ($this->afterLoadHook)($this, $content, $context) : null;
    }

    public function executeOnError(\Exception $error, array $context = []): mixed
    {
        // Dispatch JavaScript event
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:error', {
                detail: {
                    tabId: '{$this->id}',
                    error: '"
        . addslashes($error->getMessage())
        . "',
                    context: "
        . json_encode($context)
        . '
                }
            }))
        ');

        return $this->onErrorHook ? ($this->onErrorHook)($this, $error, $context) : null;
    }

    public function executeOnSwitch(string $fromTabId, string $toTabId, array $context = []): mixed
    {
        // Dispatch JavaScript event
        $this->js("
            window.dispatchEvent(new CustomEvent('tab:switch', {
                detail: {
                    fromTabId: '{$fromTabId}',
                    toTabId: '{$toTabId}',
                    context: "
        . json_encode($context)
        . '
                }
            }))
        ');

        return $this->onSwitchHook ? ($this->onSwitchHook)($this, $fromTabId, $toTabId, $context) : null;
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
     * Execute JavaScript for this tab with access to $this->js()
     */
    public function executeJs(string $js): void
    {
        $this->js($js);
    }

    /**
     * Redirect with Livewire navigation for this tab
     */
    public function navigateToTab(string $url): void
    {
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
