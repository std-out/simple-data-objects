<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Laravel\Console\ClearCommand;
use StdOut\SimpleDataObjects\Laravel\Console\MakeDataCommand;
use StdOut\SimpleDataObjects\Laravel\Console\WarmCommand;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

final class SimpleDataObjectsServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__.'/../../config/simple-data-objects.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'simple-data-objects');

        $this->registerControllerInjection();
    }

    public function boot(): void
    {
        // publishes()/commands() only have any effect once the console
        // kernel actually runs an Artisan command — registering them
        // unconditionally costs nothing outside a console context, so
        // there's no need to gate this behind runningInConsole().
        $this->publishes([
            self::CONFIG_PATH => $this->app->configPath('simple-data-objects.php'),
        ], 'simple-data-objects-config');

        $this->commands([
            WarmCommand::class,
            ClearCommand::class,
            MakeDataCommand::class,
        ]);

        // Laravel 11+ only — `optimizes()` doesn't exist on the 10.x base
        // ServiceProvider, so `php artisan optimize` simply won't pick these
        // up there; the commands still work standalone.
        if (method_exists($this, 'optimizes')) {
            $this->optimizes(optimize: 'sdo:warm', clear: 'sdo:clear');
        }

        $cachePath = $this->app->make('config')->get('simple-data-objects.cache_path');

        if ($cachePath !== null) {
            MetadataRegistry::setStoragePath($cachePath);
        }
    }

    /**
     * Auto-injects BaseData subclasses as controller-method (or any
     * container-resolved) parameters, hydrated from the current request —
     * spatie-style, no FormRequest needed. Registered on BaseData::class
     * specifically (not the parameterless global-callback form), so the
     * container only fires this for BaseData-or-subclass resolutions.
     */
    private function registerControllerInjection(): void
    {
        $this->app->beforeResolving(BaseData::class, function (string $class, array $parameters, Container $container): void {
            if (! $container->make('config')->get('simple-data-objects.inject_from_request', true)) {
                return;
            }

            if ($container->bound($class)) {
                return;
            }

            // The container only fires this for BaseData::class or an
            // existing subclass (see Container::fireBeforeResolvingCallbacks),
            // so $class is guaranteed to exist here — only abstract
            // intermediate base classes (e.g. an app's own AppData) need
            // filtering out.
            if ((new ReflectionClass($class))->isAbstract()) {
                return;
            }

            // Not using HasLaravelIntegration — no fromRequest() to call.
            if (! method_exists($class, 'fromRequest')) {
                return;
            }

            // No request bound at all (bare container, no booted HTTP/console
            // kernel) — let the container fail normally instead of crashing
            // inside fromRequest() with a confusing error. Note: Laravel's
            // own console kernel binds a synthetic empty Request too, so
            // this only guards the bare-container edge case, not "am I in
            // an HTTP request" — see docs/laravel/service-provider.md.
            if (! $container->bound('request')) {
                return;
            }

            $container->bind($class, static fn (Container $c) => $class::fromRequest($c->make(Request::class)));
        });
    }
}
