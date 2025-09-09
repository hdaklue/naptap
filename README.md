# NapTab

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hdaklue/naptab.svg?style=flat-square)](https://packagist.org/packages/hdaklue/naptab)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/hdaklue/naptab/run-tests?label=tests)](https://github.com/hdaklue/naptab/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hdaklue/naptab.svg?style=flat-square)](https://packagist.org/packages/hdaklue/naptab)

**Advanced Laravel tabs component with Livewire 3, featuring device-specific navigation, URL routing, extensive theming, and mobile-first design.**

Gone are the days of basic, rigid tab implementations that break on mobile devices and offer minimal customization. NapTab delivers a sophisticated, production-ready tabs solution that adapts intelligently to different devices, provides extensive theming options, and integrates seamlessly with modern Laravel applications.

## Why NapTab?

**The Problem**: Most tab implementations are afterthoughts—basic HTML with minimal styling that becomes unusable on mobile devices, lacks proper accessibility, and requires extensive custom CSS for professional appearance.

**The Solution**: NapTab is a comprehensive tabs system built specifically for modern web applications, offering device-aware navigation, professional theming, and developer-friendly APIs.

### Key Advantages

- **🎯 Device-Specific Navigation**: Automatically switches between desktop scroll, mobile scroll, and modal navigation based on device detection
- **🎨 22 Professional Color Schemes**: From subtle grays to vibrant brand colors, all with proper dark mode contrast
- **📱 Mobile-First Design**: Responsive by default with touch-friendly interactions and optimized spacing
- **⚡ Performance Optimized**: Lazy loading, caching, and minimal DOM updates for smooth user experience
- **♿ Accessibility Built-In**: ARIA compliant with keyboard navigation and screen reader support
- **🛣️ URL Routing**: Clean URL patterns with `{activeTab?}` parameter support for bookmarkable tabs
- **🔧 Zero Config to Full Control**: Works out-of-the-box but offers extensive customization when needed

## Installation & Setup

### Quick Install

```bash
composer require hdaklue/naptab
php artisan naptab:install
```

The installation command automatically:
- Publishes CSS assets to avoid Tailwind purging issues
- Creates a service provider stub in your `app/Providers` directory
- Configures Tailwind CSS safelist for all theme colors
- Sets up optimal default configuration

### Requirements

- **PHP**: 8.1+
- **Laravel**: 10.0+, 11.0+, or 12.0+
- **Livewire**: 3.0+
- **Tailwind CSS**: Any version (v4 compatible)
- **Icons**: Blade UI Icons with Heroicons

## Quick Start

### 1. Create Your First Tabs Component

```php
<?php

use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\UI\Tab;

class DashboardTabs extends NapTab
{
    protected function tabs(): array
    {
        return [
            Tab::make('overview', 'Overview')
                ->icon('chart-bar')
                ->badge('5'),
                
            Tab::make('analytics', 'Analytics')
                ->icon('presentation-chart-line')
                ->badge('New'),
                
            Tab::make('settings', 'Settings')
                ->icon('cog-6-tooth')
                ->visible(fn() => auth()->user()->can('manage-settings')),
        ];
    }

    public function overview()
    {
        // This method is called only when the tab is accessed
        $metrics = $this->getOverviewMetrics();
        
        return view('dashboard.overview', compact('metrics'));
    }

    public function analytics()
    {
        // Lazy loaded - only executed when user clicks the tab
        $data = $this->getAnalyticsData();
        
        return view('dashboard.analytics', compact('data'));
    }

    public function settings()
    {
        return view('dashboard.settings');
    }
}
```

### 2. Add to Your Blade Template

```blade
<!-- Simple usage -->
<livewire:dashboard-tabs />

<!-- With URL routing -->
<livewire:dashboard-tabs :active-tab="request('activeTab', 'overview')" />

<!-- With custom classes -->
<div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6">
    <livewire:dashboard-tabs />
</div>
```

That's it! Your tabs now feature:
- ✅ Device-aware navigation (scroll on desktop, modal on mobile)
- ✅ Professional styling with proper dark mode
- ✅ Lazy loading - tab content loads only when accessed
- ✅ Accessibility features and keyboard navigation
- ✅ URL-based routing for bookmarkable tabs

## Advanced Features

### Device-Specific Navigation

NapTab intelligently adapts its navigation based on the user's device:

**Desktop Experience:**
- Horizontal scrolling navigation with smooth animations
- Hover states and focus indicators
- Optimized for mouse interaction

**Mobile Experience:**
- Compact scroll navigation for landscape orientation
- Modal navigation with hamburger menu for portrait
- Touch-friendly tap targets and swipe gestures

**Automatic Detection:**
```php
// Uses jenssegers/agent for device detection
// No configuration needed - works automatically

// Manual override if needed
Tab::make('mobile-only', 'Mobile Features')
    ->visible(fn() => request()->userAgent()->isMobile());
```

### Professional Theming System

Choose from 22 carefully crafted color schemes, each optimized for both light and dark modes:

```php
// In your app/Providers/NapTabServiceProvider.php
use Hdaklue\NapTab\Services\NapTabConfig;
use Hdaklue\NapTab\Enums\{TabStyle, TabColor, TabBorderRadius, Shadow};

public function boot()
{
    $this->app->singleton('naptab.config', function () {
        return NapTabConfig::create()
            ->style(TabStyle::Modern)           // Modern, Minimal, or Sharp
            ->color(TabColor::Indigo, TabColor::Gray)  // Primary & secondary
            ->radius(TabBorderRadius::Large)    // Border radius
            ->shadow(Shadow::Large)             // Drop shadows
            ->spacing(TabSpacing::Large);       // Generous spacing
    });
}
```

**Available Themes:**
- **Tech**: `Blue`, `Indigo`, `Purple`, `Sky` - Perfect for SaaS applications
- **Business**: `Slate`, `Gray`, `Neutral` - Professional corporate look  
- **Brand**: `Red`, `Green`, `Yellow`, `Pink` - Match your brand colors
- **Premium**: `Emerald`, `Teal`, `Orange`, `Rose` - Distinctive, modern feel

### URL Routing & Bookmarkable Tabs

Create clean, SEO-friendly URLs that users can bookmark and share:

```php
// Routes automatically generated:
// /dashboard?activeTab=overview
// /dashboard?activeTab=analytics
// /dashboard?activeTab=settings

class DashboardTabs extends NapTab
{
    public function baseRoute(): string
    {
        // Auto-detects current route with {activeTab?} parameter
        return route('dashboard', ['activeTab' => '{activeTab?}']);
    }
    
    // Or disable routing entirely
    public function baseRoute(): null
    {
        return null; // Disables URL routing
    }
}
```

**Route Definition:**
```php
// In your web.php routes
Route::get('/dashboard/{activeTab?}', DashboardController::class)
    ->name('dashboard');
```

### Advanced Tab Features

**Conditional Visibility & Authorization:**
```php
Tab::make('admin', 'Admin Panel')
    ->icon('shield-check')
    ->visible(fn() => auth()->user()->isAdmin())
    ->authorizeAccess(fn() => Gate::allows('admin-panel'));
```

**Dynamic Badges:**
```php
Tab::make('notifications', 'Notifications')
    ->icon('bell')
    ->badge(fn() => auth()->user()->unreadNotifications()->count())
    ->badgeColor('red'); // Highlight urgent items
```

**Loading States & Error Handling:**
```php
public function analytics()
{
    try {
        $data = $this->getAnalyticsData();
        return view('dashboard.analytics', compact('data'));
    } catch (Exception $e) {
        // Automatically handled with error fallback UI
        throw $e;
    }
}
```

## Configuration Options

### Global Configuration

Configure NapTab globally in your service provider:

```php
use Hdaklue\NapTab\Services\NapTabConfig;
use Hdaklue\NapTab\Enums\*;

NapTabConfig::create()
    // Visual Style
    ->style(TabStyle::Modern)                    // Modern | Minimal | Sharp
    ->color(TabColor::Blue, TabColor::Gray)      // Primary, Secondary
    ->radius(TabBorderRadius::Medium)            // None to Full
    ->shadow(Shadow::Large)                      // None to XL
    ->spacing(TabSpacing::Normal)                // Small | Normal | Large
    
    // Behavior
    ->enableRouting(true)                        // URL-based routing
    ->enableAnimations(true)                     // Smooth transitions
    ->mobileModalBreakpoint(768)                 // px width for modal switch
    
    // Performance
    ->enableCaching(true)                        // Cache tab content
    ->lazyLoad(true)                            // Load content on demand
    ->prefetchNext(false);                      // Prefetch adjacent tabs
```

### Per-Component Configuration

Override global settings for specific tab components:

```php
class SpecialTabs extends NapTab
{
    protected function config(): array
    {
        return [
            'style' => TabStyle::Sharp,
            'primary_color' => TabColor::Red,
            'enable_routing' => false,
        ];
    }
}
```

## Mobile-First Design

### Responsive Breakpoints

NapTab adapts its layout based on screen size:

- **Large screens (1024px+)**: Full desktop experience with hover states
- **Medium screens (768-1023px)**: Tablet-optimized with touch targets
- **Small screens (<768px)**: Mobile navigation (scroll or modal)

### Touch-Friendly Features

- **Increased tap targets**: 44px minimum for comfortable interaction
- **Swipe gestures**: Swipe between tabs on mobile devices
- **Modal navigation**: Hamburger menu for portrait mobile orientation
- **Smooth animations**: 60fps animations optimized for mobile

### Accessibility

**Keyboard Navigation:**
- `Tab` / `Shift+Tab`: Navigate between tabs
- `Enter` / `Space`: Activate selected tab
- `Arrow Keys`: Move between tabs in tab panel

**Screen Reader Support:**
- Proper ARIA labels and roles
- Live region announcements for tab changes
- Semantic HTML structure

**Focus Management:**
- Visible focus indicators
- Logical tab order
- Skip links for keyboard users

## Styling & Customization

### Published CSS Assets

NapTab publishes CSS files to prevent Tailwind purging issues:

```css
/* resources/css/naptab.css - Core styles */
.naptab-container { /* Base container styles */ }
.naptab-nav { /* Navigation styles */ }
.naptab-tab { /* Individual tab styles */ }

/* resources/css/naptab-safelist.css - Tailwind safelist */
/* All theme colors preserved for dynamic usage */
```

### Custom Styling

**Option 1: Override CSS Classes**
```css
/* In your app.css */
.naptab-tab {
    @apply font-semibold tracking-wide;
}

.naptab-tab--active {
    @apply ring-2 ring-blue-500 ring-offset-2;
}
```

**Option 2: Publish and Modify Views**
```bash
php artisan vendor:publish --tag="naptab-views"
```

**Option 3: Custom Theme Colors**
```php
// Add your own color scheme
TabColor::register('brand', [
    'primary' => 'bg-brand-500 text-white',
    'secondary' => 'bg-gray-100 text-gray-600',
    'dark_primary' => 'bg-brand-400 text-gray-900',
    'dark_secondary' => 'bg-gray-800 text-gray-300',
]);
```

## Performance Optimization

### Lazy Loading

Tab content is only loaded when accessed:

```php
public function expensiveReport()
{
    // This heavy computation only runs when user clicks the tab
    $report = $this->generateComplexReport();
    
    return view('reports.expensive', compact('report'));
}
```

### Caching Support

Enable caching for frequently accessed tab content:

```php
class CachedTabs extends NapTab
{
    protected function cacheKey(string $tabId): string
    {
        return "tabs.{$this->id}.{$tabId}." . auth()->id();
    }
    
    protected function cacheDuration(): int
    {
        return 300; // 5 minutes
    }
}
```

### Minimal DOM Updates

- Only active tab content is rendered
- Navigation updates use Alpine.js for smooth transitions
- Optimized HTML structure reduces payload size

## Examples & Use Cases

### E-commerce Product Details

```php
class ProductTabs extends NapTab
{
    public Product $product;
    
    protected function tabs(): array
    {
        return [
            Tab::make('description', 'Description')
                ->icon('document-text'),
                
            Tab::make('specifications', 'Specifications')
                ->icon('clipboard-document-list'),
                
            Tab::make('reviews', 'Reviews')
                ->icon('star')
                ->badge(fn() => $this->product->reviews()->count()),
                
            Tab::make('shipping', 'Shipping')
                ->icon('truck'),
        ];
    }
    
    public function reviews()
    {
        $reviews = $this->product->reviews()
            ->with('user')
            ->latest()
            ->paginate(10);
            
        return view('product.reviews', compact('reviews'));
    }
}
```

### Admin Dashboard

```php
class AdminDashboard extends NapTab
{
    protected function tabs(): array
    {
        return [
            Tab::make('overview', 'Overview')
                ->icon('chart-bar'),
                
            Tab::make('users', 'Users')
                ->icon('users')
                ->badge(fn() => User::pending()->count())
                ->visible(fn() => Gate::allows('manage-users')),
                
            Tab::make('analytics', 'Analytics')
                ->icon('chart-pie')
                ->authorizeAccess(fn() => auth()->user()->hasRole('analyst')),
                
            Tab::make('settings', 'Settings')
                ->icon('cog-6-tooth')
                ->visible(fn() => auth()->user()->isAdmin()),
        ];
    }
}
```

### User Profile Settings

```php
class ProfileTabs extends NapTab
{
    protected function tabs(): array
    {
        return [
            Tab::make('profile', 'Profile')
                ->icon('user-circle'),
                
            Tab::make('security', 'Security')
                ->icon('shield-check')
                ->badge(fn() => auth()->user()->needsPasswordUpdate() ? '!' : null),
                
            Tab::make('notifications', 'Notifications')
                ->icon('bell'),
                
            Tab::make('billing', 'Billing')
                ->icon('credit-card')
                ->visible(fn() => config('billing.enabled')),
        ];
    }
}
```

## Troubleshooting

### Common Issues

**Tabs not showing properly:**
```bash
# Ensure CSS assets are published
php artisan naptab:install --force

# Check Tailwind configuration includes published CSS
# In your tailwind.config.js:
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './public/css/naptab*.css', // Add this line
  ],
}
```

**Mobile navigation not working:**
```php
// Verify jenssegers/agent is installed
composer require jenssegers/agent

// Check device detection
use Jenssegers\Agent\Agent;
$agent = new Agent();
dd($agent->isMobile()); // Should return true/false
```

**URL routing not working:**
```php
// Ensure route parameter is optional
Route::get('/dashboard/{activeTab?}', DashboardController::class);

// Check baseRoute() method returns correct pattern
public function baseRoute(): string
{
    return route('dashboard', ['activeTab' => '{activeTab?}']);
}
```

### Performance Issues

**Slow tab switching:**
- Enable caching for expensive operations
- Use lazy loading for heavy content
- Optimize database queries in tab methods

**Large bundle size:**
- Verify CSS assets are published (prevents Tailwind purging)
- Use specific color imports instead of all themes
- Consider lazy loading tab components

### Browser Compatibility

- **Modern browsers**: Full feature support
- **IE11**: Basic functionality (no advanced animations)
- **Safari iOS**: Optimized touch interactions
- **Chrome Android**: Full mobile experience

## Contributing

We welcome contributions to NapTab! Here's how you can help:

### Development Setup

```bash
git clone https://github.com/hdaklue/naptab.git
cd naptab
composer install
npm install
```

### Running Tests

```bash
composer test
composer test-coverage
```

### Coding Standards

```bash
composer pint        # Fix code style
composer analyse     # Run PHPStan analysis
```

## Security

If you discover any security-related issues, please email hassan@daklue.com instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- **[Hassan Ibrahim](https://github.com/hdaklue)** - Creator & Maintainer
- **[All Contributors](../../contributors)** - Community contributions
- **[Laravel](https://laravel.com)** - The foundation
- **[Livewire](https://livewire.laravel.com)** - Reactive components
- **[Tailwind CSS](https://tailwindcss.com)** - Styling framework

---

**Ready to elevate your Laravel application with professional tabs?**

```bash
composer require hdaklue/naptab
php artisan naptab:install
```

Transform your user interface from basic tabs to a sophisticated, mobile-first experience that your users will love and your team will enjoy maintaining.