<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Laravel;

use Orchestra\Testbench\TestCase as BaseTestCase;
use StdOut\SimpleDataObjects\Laravel\SimpleDataObjectsServiceProvider;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

/**
 * Shared Testbench bootstrap for the ServiceProvider/artisan-command/
 * controller-injection tests — the only tests in this suite that need a
 * real, booted Laravel application rather than a bare container or
 * hand-constructed Request/Model (see HasLaravelIntegrationTest for that
 * lighter-weight precedent).
 */
abstract class TestCase extends BaseTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [SimpleDataObjectsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function tearDown(): void
    {
        // MetadataRegistry is process-global static state — reset it so a
        // cache path configured in one test never leaks into unrelated
        // tests elsewhere in the suite. Tests that write real cache files
        // are responsible for removing their own temp directory.
        MetadataRegistry::setStoragePath('');
        MetadataRegistry::flush();

        parent::tearDown();
    }
}
