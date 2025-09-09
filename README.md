# NapTab - Laravel Livewire Tabs Component

A powerful, customizable Livewire tabs component for Laravel with RTL support, multiple styles, and comprehensive configuration options.

## Features

- ✅ **Multiple Styles**: Modern, Minimal, and Sharp design presets
- ✅ **RTL Support**: Full right-to-left language support with logical CSS properties
- ✅ **Customizable**: Extensive configuration options for colors, spacing, shadows, and borders
- ✅ **Responsive**: Content-based tab sizing with horizontal scroll support
- ✅ **Accessible**: ARIA compliant with keyboard navigation support
- ✅ **Dark Mode**: Built-in dark mode support
- ✅ **Icons & Badges**: Blade UI Icons integration with customizable badges
- ✅ **Performance**: Optimized rendering with minimal DOM updates

## Installation

You can install the package via Composer:

```bash
composer require hdaklu/naptab
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="naptab-config"
```

Publish the views (optional):

```bash
php artisan vendor:publish --tag="naptab-views"
```

## Quick Start

### Basic Usage

```php
use Hdaklu\NapTab\UI\Tab;
use Hdaklu\NapTab\Livewire\NapTab;
use Illuminate\Support\Collection;

class MyTabs extends NapTab
{
    protected function tabs(): Collection
    {
        return collect([
            Tab::make('overview', 'Overview')
                ->icon('chart-bar')
                ->badge('5'),
                
            Tab::make('settings', 'Settings')
                ->icon('cog-6-tooth'),
                
            Tab::make('users', 'Users')
                ->icon('users')
                ->badge('12')
                ->visible(fn() => auth()->user()->can('view-users')),
        ]);
    }

    public function overview()
    {
        return view('tabs.overview');
    }

    public function settings()
    {
        return view('tabs.settings');
    }

    public function users()
    {
        return view('tabs.users');
    }
}
```

### In Your Blade Template

```blade
<livewire:my-tabs />
```

## Configuration

### Service Provider Configuration

Configure NapTab globally in your `AppServiceProvider`:

```php
use Hdaklu\NapTab\Services\NapTabConfig;
use Hdaklu\NapTab\Enums\TabStyle;
use Hdaklu\NapTab\Enums\TabColor;
use Hdaklu\NapTab\Enums\TabBorderRadius;
use Hdaklu\NapTab\Enums\TabSpacing;

public function boot()
{
    // Override the default NapTab configuration
    $this->app->singleton('naptab.config', function () {
        return NapTabConfig::create()
            ->style(TabStyle::MODERN)
            ->color(TabColor::Blue, TabColor::Gray)
            ->radius(TabBorderRadius::Medium)
            ->spacing(TabSpacing::NORMAL)
            ->shadow(Shadow::LARGE);
    });
}
```

### Available Styles

```php
// Clean design with minimal styling
TabStyle::MINIMAL

// Balanced design with shadows and smooth transitions  
TabStyle::MODERN

// Bold design with sharp edges
TabStyle::SHARP
```

### Available Colors

```php
TabColor::Blue      // Primary: Blue, Secondary: Gray
TabColor::Red       // Primary: Red, Secondary: Gray  
TabColor::Green     // Primary: Green, Secondary: Gray
TabColor::Yellow    // Primary: Yellow, Secondary: Gray
TabColor::Purple    // Primary: Purple, Secondary: Gray
TabColor::Pink      // Primary: Pink, Secondary: Gray
TabColor::Indigo    // Primary: Indigo, Secondary: Gray
TabColor::Gray      // Primary: Gray, Secondary: Slate
TabColor::Slate     // Primary: Slate, Secondary: Gray
TabColor::Sky       // Primary: Sky, Secondary: Gray
TabColor::Emerald   // Primary: Emerald, Secondary: Gray
```

### Border Radius Options

```php
TabBorderRadius::None    // rounded-none (0px)
TabBorderRadius::Small   // rounded-sm (2px)  
TabBorderRadius::Medium  // rounded-md (6px)
TabBorderRadius::Large   // rounded-lg (8px)
TabBorderRadius::Full    // rounded-full
```

### Spacing Options

```php
TabSpacing::SMALL   // Compact spacing for dense layouts
TabSpacing::NORMAL  // Standard spacing for balanced appearance
TabSpacing::LARGE   // Generous spacing for spacious layouts
```

## Advanced Usage

### Tab Authorization

```php
Tab::make('admin', 'Admin Panel')
    ->authorizeAccess(fn() => auth()->user()->isAdmin())
    ->visible(fn() => config('app.debug'));
```

### Tab Hooks with Livewire Integration

Tabs extend Livewire Component, giving you access to `$this->js()`, `$this->dispatch()`, and all Livewire features:

```php
Tab::make('analytics', 'Analytics')
    ->beforeLoad(function (Tab $tab, array $context) {
        // Execute before tab content loads
        Log::info('Loading analytics tab', $context);
        
        // Dispatch Livewire events
        $tab->dispatchTabEvent('loading', ['message' => 'Analytics loading...']);
        
        // Execute JavaScript directly
        $tab->executeJs("
            console.log('Analytics tab loading...');
            showLoadingSpinner();
        ");
        
        // Cancel loading if needed
        if (!someCondition()) {
            return ['cancel' => true, 'message' => 'Analytics unavailable'];
        }
    })
    ->afterLoad(function (Tab $tab, string $content, array $context) {
        // Execute after tab content loads
        Log::info('Analytics tab loaded', ['length' => strlen($content)]);
        
        // Execute JavaScript with data
        $tab->executeJs("
            hideLoadingSpinner();
            trackAnalytics('tab_loaded', { tabId: '{$tab->getId()}' });
        ");
        
        // Dispatch success event to other Livewire components
        $tab->dispatchTabEvent('loaded', [
            'contentLength' => strlen($content),
            'timestamp' => now()
        ]);
        
        // Modify content if needed
        return ['modifiedContent' => $content . '<div>Loaded at ' . now() . '</div>'];
    })
    ->onError(function (Tab $tab, Exception $error, array $context) {
        // Handle loading errors
        Log::error('Analytics tab failed', ['error' => $error->getMessage()]);
        
        // Show error notification with JavaScript
        $tab->executeJs("
            showNotification('Error loading analytics: {$error->getMessage()}', 'error');
        ");
        
        // Dispatch error event
        $tab->dispatchTabEvent('error', ['error' => $error->getMessage()]);
        
        // Provide fallback content
        return ['fallbackContent' => '<div class=\"text-red-500\">Analytics temporarily unavailable</div>'];
    })
    ->onSwitch(function (Tab $tab, string $fromTabId, string $toTabId, array $context) {
        // Execute when switching to this tab
        Log::info('Switching to analytics', ['from' => $fromTabId, 'to' => $toTabId]);
        
        // Animate tab switch with JavaScript
        $tab->executeJs("
            animateTabSwitch('{$fromTabId}', '{$toTabId}');
            updateBreadcrumb('Analytics');
        ");
        
        // Track navigation
        $tab->dispatchTabEvent('switched', [
            'from' => $fromTabId,
            'to' => $toTabId,
            'timestamp' => now()
        ]);
        
        // Prevent switching if needed
        if (!userCanViewAnalytics()) {
            $tab->executeJs("showAccessDeniedModal();");
            return ['cancel' => true, 'message' => 'Access denied'];
        }
    });
```

### Frontend JavaScript Integration

Listen for tab events in your JavaScript:

```javascript
// Listen for Livewire tab events
Livewire.on('tab:loading', (data) => {
    console.log('Tab loading:', data.tabId);
});

Livewire.on('tab:loaded', (data) => {
    console.log('Tab loaded:', data.tabId, 'Content length:', data.contentLength);
});

// Listen for custom events dispatched by tab hooks
window.addEventListener('tab:beforeLoad', (event) => {
    console.log('Before load:', event.detail.tabId);
});

window.addEventListener('tab:afterLoad', (event) => {
    console.log('After load:', event.detail.tabId, 'Length:', event.detail.contentLength);
});

window.addEventListener('tab:error', (event) => {
    console.error('Tab error:', event.detail.tabId, event.detail.error);
});
```

### Custom Tab Content

```php
public function customTab()
{
    $data = $this->getCustomData();
    
    return view('tabs.custom', compact('data'));
}
```

### RTL Support

NapTab automatically detects RTL languages and applies appropriate styling:

```php
// Automatic RTL detection for these locales
app()->setLocale('ar'); // Arabic
app()->setLocale('he'); // Hebrew  
app()->setLocale('fa'); // Persian

// Manual RTL configuration
config(['app.direction' => 'rtl']);
```

### Blade UI Icons Integration

NapTab uses Blade UI Icons with Heroicons:

```php
Tab::make('dashboard', 'Dashboard')
    ->icon('chart-bar')        // Uses heroicon-o-chart-bar
    ->icon('cog-6-tooth')      // Uses heroicon-o-cog-6-tooth
    ->icon('users');           // Uses heroicon-o-users
```

## Styling

### Custom CSS

The component uses Tailwind CSS classes. You can customize the appearance by:

1. Publishing the views and modifying the templates
2. Adding custom CSS classes
3. Using the configuration options

### Dark Mode

Dark mode is automatically supported:

```html
<html class="dark">
    <!-- Your content -->
</html>
```

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+
- Livewire 3.0+
- Blade UI Icons 2.0+
- Tailwind CSS

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Hassan Ibrahim](https://github.com/hdaklu)
- [All Contributors](../../contributors)

## Support

If you discover any security-related issues, please email hassan@daklue.com instead of using the issue tracker.