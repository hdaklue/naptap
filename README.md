# NapTab

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hdaklue/naptab.svg?style=flat-square)](https://packagist.org/packages/hdaklue/naptab)
[![Total Downloads](https://img.shields.io/packagist/dt/hdaklue/naptab.svg?style=flat-square)](https://packagist.org/packages/hdaklue/naptab)

**Smart Laravel tabs component with Livewire 3 where tabs "nap" until needed - featuring true lazy loading, mobile-first design, and RTL support.**

Most tab implementations load all content upfront, wasting resources and slowing your application. NapTab takes a different approach: tabs sleep (nap) until users actually need them, dramatically improving performance and user experience.

## Why NapTab?

**The Problem**: Traditional tabs load all content immediately, causing:
- Slow initial page loads with heavy database queries running for hidden content
- Wasted server resources processing data users may never see
- Poor mobile experience with cramped navigation
- No RTL support for international applications

**The Solution**: NapTab introduces "sleeping tabs" - content only loads when users actually click the tab, plus mobile-first navigation and complete RTL support.

### Core Features

- **😴 True Lazy Loading ("Nap" Mode)**: Tabs sleep until awakened - content loads only when clicked, dramatically improving performance
- **📱 Mobile-First Navigation**: Intelligent responsive design with device-specific navigation patterns  
- **🌐 Complete RTL Support**: Built-in right-to-left language support with logical CSS properties
- **⚡ Performance Focused**: Zero wasted resources - only active tabs consume server processing
- **🎯 Smart Caching**: Avoid re-loading tab content with intelligent caching strategies
- **🔧 Developer Friendly**: Simple API with powerful customization options

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

That's it! Your tabs are now "napping" and will only wake up when needed:
- ✅ **True Lazy Loading** - Heavy database queries run only when users click tabs
- ✅ **Performance Boost** - Page loads instantly, content loads on-demand  
- ✅ **Mobile-First** - Intelligent navigation that adapts to device type
- ✅ **RTL Ready** - Perfect right-to-left support for Arabic, Hebrew, Persian
- ✅ **Resource Efficient** - Zero wasted server processing for unused tabs

## Tab API Reference

### Complete Tab Configuration

NapTab provides multiple ways to define tab content, giving you flexibility for different use cases:

```php
class ComprehensiveTabs extends NapTab
{
    protected function tabs(): array
    {
        return [
            // Method 1: Controller Method (Recommended for complex logic)
            Tab::make('dashboard', 'Dashboard')
                ->icon('chart-bar')
                ->badge(fn() => $this->getNotificationCount())
                ->visible(fn() => auth()->check())
                ->disabled(fn() => $this->isMaintenanceMode()),
                
            // Method 2: Direct Content (Simple HTML/Blade)
            Tab::make('about', 'About Us')
                ->icon('information-circle')
                ->content('<div class="p-4">
                    <h2>About Our Company</h2>
                    <p>We are a leading provider...</p>
                </div>'),
                
            // Method 3: Blade View (Static content)
            Tab::make('contact', 'Contact')
                ->icon('envelope')
                ->content(view('pages.contact')),
                
            // Method 4: Livewire Component (Interactive content)
            Tab::make('settings', 'Settings')
                ->icon('cog-6-tooth')
                ->livewire('user-settings', ['userId' => auth()->id()])
                ->visible(fn() => auth()->user()->can('manage-settings')),
                
            // Method 5: Advanced Configuration
            Tab::make('analytics', 'Analytics')
                ->icon('presentation-chart-line')
                ->badge('Pro')
                ->badgeColor('green')
                ->tooltip('Advanced analytics and reporting')
                ->authorizeAccess(fn() => Gate::allows('view-analytics'))
                ->cacheFor(300) // Cache for 5 minutes
                ->preload(true) // Load immediately (skip nap mode)
                ->group('reports'), // Group tabs for organization
        ];
    }

    // Controller method for Method 1
    public function dashboard()
    {
        // Heavy computation only runs when tab is clicked
        $metrics = $this->calculateDashboardMetrics();
        $charts = $this->generateChartData();
        
        return view('dashboard.overview', compact('metrics', 'charts'));
    }
}
```

### Tab Content Methods

**1. Controller Methods (Best for Dynamic Content)**
```php
public function reports()
{
    // Database queries only execute when user clicks this tab
    $reports = Report::with('author')
        ->where('status', 'published')
        ->latest()
        ->paginate(20);
        
    return view('tabs.reports', compact('reports'));
}
```

**2. Direct Content**
```php
Tab::make('terms', 'Terms of Service')
    ->content('<div class="prose max-w-none">
        <h1>Terms of Service</h1>
        <p>By using our service...</p>
    </div>')
```

**3. Blade Views**
```php
Tab::make('faq', 'FAQ')
    ->content(view('pages.faq', ['categories' => $this->getFaqCategories()]))
```

**4. Livewire Components**
```php
Tab::make('chat', 'Live Chat')
    ->livewire('chat-widget', [
        'room' => 'support',
        'user' => auth()->user()
    ])
```

### Tab Visibility & Authorization

```php
Tab::make('admin', 'Admin Panel')
    // Simple visibility check
    ->visible(fn() => auth()->user()->isAdmin())
    
    // Authorization with Laravel Gates/Policies
    ->authorizeAccess(fn() => Gate::allows('access-admin-panel'))
    
    // Multiple conditions
    ->visible(function() {
        return auth()->check() 
            && auth()->user()->hasRole('manager')
            && config('features.admin_enabled');
    })
    
    // Disable instead of hide
    ->disabled(fn() => $this->isMaintenanceMode())
```

### Dynamic Badges & Notifications

```php
Tab::make('inbox', 'Messages')
    ->badge(fn() => auth()->user()->unreadMessages()->count())
    ->badgeColor('red') // red, blue, green, yellow, purple, pink, gray
    ->badgeVisible(fn() => auth()->user()->unreadMessages()->exists())
    
Tab::make('notifications', 'Notifications')
    ->badge(function() {
        $count = auth()->user()->unreadNotifications()->count();
        return $count > 99 ? '99+' : $count;
    })
    ->badgeColor(fn() => $count > 10 ? 'red' : 'blue')
```

### Performance Controls

```php
Tab::make('heavy-report', 'Heavy Report')
    // Cache the tab content for 10 minutes
    ->cacheFor(600)
    
    // Custom cache key
    ->cacheKey(fn() => 'report-' . auth()->id() . '-' . date('Y-m-d'))
    
    // Preload this tab (skip nap mode)
    ->preload(true)
    
    // Lazy load with custom timeout
    ->lazyLoad(true, 5000) // 5 second timeout
```

### Tab Grouping & Organization

```php
protected function tabs(): array
{
    return [
        // Main navigation
        Tab::make('overview', 'Overview')->group('main'),
        Tab::make('analytics', 'Analytics')->group('main'),
        
        // Settings group
        Tab::make('profile', 'Profile')->group('settings'),
        Tab::make('security', 'Security')->group('settings'),
        Tab::make('billing', 'Billing')->group('settings'),
        
        // Admin group (conditionally shown)
        Tab::make('users', 'User Management')
            ->group('admin')
            ->visible(fn() => auth()->user()->isAdmin()),
    ];
}

// Organize tabs by groups
public function getTabGroups(): array
{
    return [
        'main' => 'Dashboard',
        'settings' => 'Settings', 
        'admin' => 'Administration'
    ];
}
```

## Configuration Reference

### Global Configuration Options

Configure NapTab behavior globally in your `app/Providers/NapTabServiceProvider.php`:

```php
use Hdaklue\NapTab\Services\NapTabConfig;
use Hdaklue\NapTab\Enums\*;

$this->app->singleton('naptab.config', function () {
    return NapTabConfig::create()
        
        // === VISUAL STYLING ===
        ->style(TabStyle::Modern)                    // Modern | Minimal | Sharp
        ->color(TabColor::Blue, TabColor::Gray)      // Primary, Secondary colors
        ->radius(TabBorderRadius::Medium)            // None | Small | Medium | Large | Full
        ->shadow(Shadow::Large)                      // None | Small | Medium | Large | ExtraLarge
        ->spacing(TabSpacing::Normal)                // Small | Normal | Large
        
        // === BEHAVIOR SETTINGS ===
        ->navModalOnMobile(true)                     // Enable modal navigation on mobile
        ->routable(true)                             // Enable URL-based routing
        ->enableAnimations(true)                     // Smooth transitions and animations
        ->enableCaching(true)                        // Global caching for tab content
        ->defaultCacheDuration(300)                  // Default cache time in seconds
        
        // === MOBILE SETTINGS ===
        ->mobileBreakpoint(768)                      // Pixel width for mobile detection
        ->tabletBreakpoint(1024)                     // Pixel width for tablet detection
        ->swipeGestures(true)                        // Enable swipe between tabs
        ->touchScrolling(true)                       // Enable touch scrolling navigation
        
        // === RTL SUPPORT ===
        ->rtlSupport(true)                          // Enable RTL language detection
        ->rtlLanguages(['ar', 'he', 'fa', 'ur'])   // RTL language codes
        
        // === ACCESSIBILITY ===
        ->keyboardNavigation(true)                  // Enable arrow key navigation
        ->focusManagement(true)                     // Automatic focus management
        ->announceChanges(true)                     // Screen reader announcements
        
        // === PERFORMANCE ===
        ->lazyLoadByDefault(true)                   // All tabs nap by default
        ->prefetchAdjacent(false)                   // Prefetch adjacent tabs
        ->debounceTabSwitching(100)                 // Debounce rapid tab switching
        ->maxConcurrentLoads(3);                    // Limit simultaneous tab loads
});
```

### Per-Component Configuration

Override global settings for specific tab components:

```php
class SpecialTabs extends NapTab
{
    protected function configure(): array
    {
        return [
            'style' => TabStyle::Sharp,
            'primary_color' => TabColor::Red,
            'mobile_modal' => false,
            'enable_routing' => false,
            'cache_duration' => 600, // 10 minutes
        ];
    }
    
    // Or override specific methods
    public function cacheEnabled(): bool
    {
        return auth()->user()->isPremium();
    }
    
    public function mobileModalEnabled(): bool
    {
        return false; // Always use scroll navigation
    }
}
```

### Environment-Based Configuration

```php
// Different settings per environment
return NapTabConfig::create()
    ->enableCaching(app()->environment('production'))
    ->lazyLoadByDefault(!app()->environment('local'))
    ->debugMode(app()->environment('local'))
    ->style(app()->environment('production') ? TabStyle::Modern : TabStyle::Sharp);
```

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