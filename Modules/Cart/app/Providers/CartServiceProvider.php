<?php

namespace Modules\Cart\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Cart\Classes\ApiCart;
use Modules\Cart\Classes\Cart;
use Modules\Cart\Interfaces\CartInterface;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CartServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Cart';

    protected string $nameLower = 'cart';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // API Cart (Stateless) - for mobile apps, headless frontends, etc.
        $this->app->singleton('apicart', function ($app) {
            $request = $app['request'];

            // Get cart identifier from header or request
            $identifier = $request->header('X-Cart-Identifier')
                ?: $request->input('cart_identifier');

            // Validate if identifier exists in database
            if ($identifier && !$this->isValidCartIdentifier($identifier)) {
                $identifier = null; // Invalid identifier, generate new one
            }

            // Generate a new identifier if none exists or invalid
            if (!$identifier) {
                $identifier = 'api_' . md5(uniqid('cart_', true) . time());
            }

            return new ApiCart($identifier);
        });

        // Web Cart (Stateful) - for traditional web sessions
        $this->app->singleton('cart', function ($app) {
            return new Cart(
                $app['session'],
                $app['events']
            );
        });

        // Context-aware binding for CartInterface
        $this->app->bind(CartInterface::class, function ($app) {
            // Use ApiCart for API routes or when specific header is present
            if ($this->isApiRequest()) {
                return $app->make('apicart');
            }

            // Default to web session cart
            return $app->make('cart');
        });

        // Register facade alias (points to web cart by default)
        $this->app->alias('cart', Cart::class);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Determine if the current request is an API request
     */
    protected function isApiRequest(): bool
    {
        $request = app('request');

        return $request->expectsJson() ||
            $request->is('api/*') ||
            $request->header('X-Requested-With') === 'XMLHttpRequest' ||
            str_contains($request->header('Accept') ?? '', 'application/json');
    }

    protected function isValidCartIdentifier($identifier)
    {
        return \Modules\Cart\Models\Cart::where('identifier', $identifier)
            ->where('instance', 'default')
            ->active()
            ->exists();
    }
    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['cart'];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
