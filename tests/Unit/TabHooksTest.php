<?php

declare(strict_types=1);

use Hdaklue\NapTab\UI\Tab;

beforeEach(function () {
    $this->tab = Tab::make('test-tab');
});

describe('Tab Hook Execution', function () {
    it('executes beforeLoad hook with tab instance', function () {
        $hookExecuted = false;
        $receivedTab = null;
        
        $this->tab->beforeLoad(function (Tab $tab) use (&$hookExecuted, &$receivedTab) {
            $hookExecuted = true;
            $receivedTab = $tab;
            return 'hook-result';
        });

        $result = $this->tab->executeBeforeLoad();

        expect($hookExecuted)->toBeTrue();
        expect($result)->toBe('hook-result');
        expect($receivedTab)->toBe($this->tab);
        expect($receivedTab->getId())->toBe('test-tab');
    });

    it('executes afterLoad hook with tab and content', function () {
        $hookExecuted = false;
        $receivedTab = null;
        $receivedContent = null;
        
        $this->tab->afterLoad(function (Tab $tab, string $content) use (&$hookExecuted, &$receivedTab, &$receivedContent) {
            $hookExecuted = true;
            $receivedTab = $tab;
            $receivedContent = $content;
            return 'after-load-result';
        });

        $testContent = '<div>Hello World!</div>';
        $result = $this->tab->executeAfterLoad($testContent);

        expect($hookExecuted)->toBeTrue();
        expect($result)->toBe('after-load-result');
        expect($receivedTab)->toBe($this->tab);
        expect($receivedContent)->toBe($testContent);
    });

    it('executes onError hook with tab and error', function () {
        $hookExecuted = false;
        $receivedTab = null;
        $receivedError = null;
        
        $this->tab->onError(function (Tab $tab, \Exception $error) use (&$hookExecuted, &$receivedTab, &$receivedError) {
            $hookExecuted = true;
            $receivedTab = $tab;
            $receivedError = $error;
            return 'error-handled';
        });

        $testError = new \Exception('Test error message', 500);
        $result = $this->tab->executeOnError($testError);

        expect($hookExecuted)->toBeTrue();
        expect($result)->toBe('error-handled');
        expect($receivedTab)->toBe($this->tab);
        expect($receivedError)->toBe($testError);
        expect($receivedError->getMessage())->toBe('Test error message');
        expect($receivedError->getCode())->toBe(500);
    });

    it('executes onSwitch hook with tab and switch details', function () {
        $hookExecuted = false;
        $receivedTab = null;
        $receivedFromTab = null;
        $receivedToTab = null;
        
        $this->tab->onSwitch(function (Tab $tab, string $fromTabId, string $toTabId) use (&$hookExecuted, &$receivedTab, &$receivedFromTab, &$receivedToTab) {
            $hookExecuted = true;
            $receivedTab = $tab;
            $receivedFromTab = $fromTabId;
            $receivedToTab = $toTabId;
            return 'switch-handled';
        });

        $result = $this->tab->executeOnSwitch('tab-1', 'tab-2');

        expect($hookExecuted)->toBeTrue();
        expect($result)->toBe('switch-handled');
        expect($receivedTab)->toBe($this->tab);
        expect($receivedFromTab)->toBe('tab-1');
        expect($receivedToTab)->toBe('tab-2');
    });

    it('returns null when no hook is defined', function () {
        expect($this->tab->executeBeforeLoad())->toBeNull();
        expect($this->tab->executeAfterLoad('content'))->toBeNull();
        expect($this->tab->executeOnError(new \Exception()))->toBeNull();
        expect($this->tab->executeOnSwitch('from', 'to'))->toBeNull();
    });

    it('can access tab properties in hooks', function () {
        $this->tab
            ->label('Custom Label')
            ->icon('heroicon-o-home')
            ->badge('New');

        $this->tab->beforeLoad(function (Tab $tab) {
            expect($tab->getLabel())->toBe('Custom Label');
            expect($tab->getIcon())->toBe('heroicon-o-home');
            expect($tab->getBadge())->toBe('New');
            expect($tab->getId())->toBe('test-tab');
            
            return 'property-access-test';
        });

        $result = $this->tab->executeBeforeLoad();
        expect($result)->toBe('property-access-test');
    });

    it('can check hook availability', function () {
        expect($this->tab->hasBeforeLoadHook())->toBeFalse();
        expect($this->tab->hasAfterLoadHook())->toBeFalse();
        expect($this->tab->hasOnErrorHook())->toBeFalse();
        expect($this->tab->hasOnSwitchHook())->toBeFalse();

        $this->tab->beforeLoad(fn() => null);
        expect($this->tab->hasBeforeLoadHook())->toBeTrue();

        $this->tab->afterLoad(fn() => null);
        expect($this->tab->hasAfterLoadHook())->toBeTrue();

        $this->tab->onError(fn() => null);
        expect($this->tab->hasOnErrorHook())->toBeTrue();

        $this->tab->onSwitch(fn() => null);
        expect($this->tab->hasOnSwitchHook())->toBeTrue();
    });
});

describe('Tab Configuration', function () {
    it('creates tab with fluent API', function () {
        $tab = Tab::make('sample-tab')
            ->label('Sample Tab')
            ->icon('heroicon-o-star')
            ->badge('5')
            ->disabled()
            ->beforeLoad(function (Tab $tab) {
                return "loaded: {$tab->getId()}";
            });

        expect($tab->getId())->toBe('sample-tab');
        expect($tab->getLabel())->toBe('Sample Tab');
        expect($tab->getIcon())->toBe('heroicon-o-star');
        expect($tab->getBadge())->toBe('5');
        expect($tab->isDisabled())->toBeTrue();
        expect($tab->hasBeforeLoadHook())->toBeTrue();
        
        $result = $tab->executeBeforeLoad();
        expect($result)->toBe('loaded: sample-tab');
    });

    it('handles closures in configuration', function () {
        $dynamicLabel = 'Dynamic';
        
        $tab = Tab::make('dynamic-tab')
            ->label(fn() => $dynamicLabel . ' Label')
            ->icon(fn() => 'heroicon-o-' . strtolower($dynamicLabel))
            ->disabled(fn() => $dynamicLabel === 'Dynamic');

        expect($tab->getLabel())->toBe('Dynamic Label');
        expect($tab->getIcon())->toBe('heroicon-o-dynamic');
        expect($tab->isDisabled())->toBeTrue();
    });

    it('validates tab ID requirements', function () {
        expect(fn() => Tab::make(''))->toThrow(InvalidArgumentException::class);
        expect(fn() => Tab::make('invalid@id'))->toThrow(InvalidArgumentException::class);
        expect(fn() => Tab::make(str_repeat('a', 51)))->toThrow(InvalidArgumentException::class);
        
        // These should work
        expect(fn() => Tab::make('valid-id'))->not->toThrow(InvalidArgumentException::class);
        expect(fn() => Tab::make('valid_id'))->not->toThrow(InvalidArgumentException::class);
        expect(fn() => Tab::make('validId123'))->not->toThrow(InvalidArgumentException::class);
    });

    it('prevents setting both content and livewire component', function () {
        $tab = Tab::make('test');
        
        $tab->content(fn() => '<div>Content</div>');
        expect(fn() => $tab->livewire('component'))->toThrow(InvalidArgumentException::class);
        
        $tab2 = Tab::make('test2');
        $tab2->livewire('component');
        expect(fn() => $tab2->content(fn() => 'content'))->toThrow(InvalidArgumentException::class);
    });
});

describe('Real World Usage', function () {
    it('works like a simple tab configuration', function () {
        $tab = Tab::make('users')
            ->label('Users')
            ->icon('heroicon-o-users')
            ->badge('5')
            ->beforeLoad(function (Tab $tab) {
                return "Loading {$tab->getLabel()}...";
            })
            ->afterLoad(function (Tab $tab, string $content) {
                return "Loaded {$tab->getLabel()} with " . strlen($content) . " characters";
            });

        // Test configuration
        expect($tab->getId())->toBe('users');
        expect($tab->getLabel())->toBe('Users');
        expect($tab->getIcon())->toBe('heroicon-o-users');
        expect($tab->getBadge())->toBe('5');
        
        // Test hook execution
        $beforeResult = $tab->executeBeforeLoad();
        expect($beforeResult)->toBe('Loading Users...');
        
        $afterResult = $tab->executeAfterLoad('<div>User list content</div>');
        expect($afterResult)->toBe('Loaded Users with 28 characters');
    });

    it('handles error scenarios gracefully', function () {
        $tab = Tab::make('problematic-tab')
            ->label('Problematic Tab')
            ->onError(function (Tab $tab, \Exception $error) {
                return "Tab {$tab->getId()} failed: {$error->getMessage()}";
            });

        $error = new \Exception('Database connection failed');
        $result = $tab->executeOnError($error);
        
        expect($result)->toBe('Tab problematic-tab failed: Database connection failed');
    });
});