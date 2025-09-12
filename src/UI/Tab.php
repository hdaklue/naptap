<?php

declare(strict_types=1);

namespace Hdaklue\NapTab\UI;

use Closure;
use Exception;
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
 * @property-read Closure|\Illuminate\Contracts\Support\Htmlable|null $content
 * @property-read string|null $livewireComponent
 * @property-read array<string, mixed> $livewireParams
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
    /** @var Closure|array<string, mixed> */
    protected Closure|array $livewireParams = [];
    protected Closure|bool|null $visibility = null;
    protected Closure|null $beforeLoadHook = null;
    protected Closure|null $afterLoadHook = null;
    protected Closure|null $onErrorHook = null;
    protected Closure|null $onSwitchHook = null;
    protected Closure|string|Htmlable|null $beforeContent = null;
    protected Closure|string|Htmlable|null $afterContent = null;

    /**
     * @throws InvalidArgumentException When tab ID is empty or contains invalid characters
     */
    public function __construct(string $id)
    {
        if (empty(trim($id))) {
            throw new InvalidArgumentException('Tab ID cannot be empty');
        }

        // Enhanced validation: only allow safe characters for tab IDs
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id) || strlen($id) > 50) {
            throw new InvalidArgumentException(
                'Tab ID must contain only alphanumeric characters, hyphens, and underscores, and be no longer than 50 characters',
            );
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

    public function visible(Closure|bool $visible): self
    {
        $this->visibility = $visible;
        return $this;
    }

    /**
     * @throws InvalidArgumentException When Livewire component is already set
     */
    public function content(Closure|Htmlable $content): self
    {
        if ($this->livewireComponent !== null) {
            throw new InvalidArgumentException('Cannot set both contentclosure and Livewire component');
        }

        $this->content = $content;
        return $this;
    }

    /**
     * @param Closure|string $component
     * @param Closure|array<string, mixed> $params
     * @throws InvalidArgumentException When content is already set
     */
    public function livewire(Closure|string $component, Closure|array $params = []): self
    {
        if ($this->content !== null) {
            throw new InvalidArgumentException('Cannot set both contentclosure and Livewire component');
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

    public function beforeContent(Closure|string|Htmlable $content): self
    {
        $this->beforeContent = $content;
        return $this;
    }

    public function afterContent(Closure|string|Htmlable $content): self
    {
        $this->afterContent = $content;
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

    public function getContent(): null|Closure
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

    public function hasBeforeContent(): bool
    {
        return $this->beforeContent !== null;
    }

    public function hasAfterContent(): bool
    {
        return $this->afterContent !== null;
    }

    public function executeBeforeLoad(): mixed
    {
        return $this->beforeLoadHook ? ($this->beforeLoadHook)($this) : null;
    }

    public function executeAfterLoad(string $content): mixed
    {
        return $this->afterLoadHook ? ($this->afterLoadHook)($this, $content) : null;
    }

    public function executeOnError(Exception $error): mixed
    {
        return $this->onErrorHook ? ($this->onErrorHook)($this, $error) : null;
    }

    public function executeOnSwitch(string $fromTabId, string $toTabId): mixed
    {
        return $this->onSwitchHook ? ($this->onSwitchHook)($this, $fromTabId, $toTabId) : null;
    }

    public function renderContent(): string
    {
        if ($this->content === null) {
            return '';
        }
        if ($this->content instanceof Htmlable) {
            return $this->content->toHtml();
        }

        // HandleClosure callables
        if ($this->content instanceof Closure) {
            $result = ($this->content)();
            return is_string($result) ? $result : '';
        }

        return '';
    }

    public function renderBeforeContent(): string
    {
        if ($this->beforeContent === null) {
            return '';
        }

        return $this->renderContentValue($this->beforeContent);
    }

    public function renderAfterContent(): string
    {
        if ($this->afterContent === null) {
            return '';
        }

        return $this->renderContentValue($this->afterContent);
    }

    private function renderContentValue(mixed $content): string
    {
        // Handle Htmlable objects
        if ($content instanceof Htmlable) {
            return $content->toHtml();
        }

        // Handle Closure callables
        if ($content instanceof Closure) {
            $result = $this->evaluate($content);
            return is_string($result) ? $result : '';
        }

        // Handle string values
        if (is_string($content)) {
            return $content;
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
        return app(NapTabConfig::class);
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
    /**
     * @param array<string, mixed> $data
     */
    public function dispatchTabEvent(string $event, array $data = []): void
    {
        $this->dispatch("tab:{$event}", array_merge(['tabId' => $this->id], $data));
    }

    /**
     * Redirect with Livewire navigation for this tab
     *
     * @throws InvalidArgumentException When URL is invalid or uses disallowed schemes
     */
    public function navigateToTab(string $url): void
    {
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/')) {
            throw new InvalidArgumentException('Invalid URL provided for navigation');
        }

        // Additional validation for absolute URLs
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);

            // Only allow HTTP and HTTPS schemes to prevent XSS via javascript: or data: URIs
            $allowedSchemes = ['http', 'https'];
            $scheme = $parsedUrl['scheme'] ?? '';

            if (!in_array($scheme, $allowedSchemes, true)) {
                throw new InvalidArgumentException('Only HTTP and HTTPS URLs are allowed');
            }

            // Prevent external redirects for security
            $currentHost = request()->getHost();
            if (isset($parsedUrl['host']) && $parsedUrl['host'] !== $currentHost) {
                throw new InvalidArgumentException('External redirects are not allowed');
            }
        }

        $this->redirect($url, navigate: true);
    }

    /**
     * Evaluate a property value - if it's aclosure, call it, otherwise return as-is
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
