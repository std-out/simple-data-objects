<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Support\Facades\Artisan;
use StdOut\SimpleDataObjects\Laravel\SimpleDataObjectsServiceProvider;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Laravel\TestCase;

class ServiceProviderTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().'/sdo_provider_test_'.uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir.'/*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($this->cacheDir);
        }

        parent::tearDown();
    }

    public function test_config_is_merged_with_defaults(): void
    {
        $this->assertTrue(config('simple-data-objects.inject_from_request'));
        $this->assertSame([app_path('Data')], config('simple-data-objects.paths'));
        $this->assertNull(config('simple-data-objects.cache_path'));
    }

    public function test_sdo_warm_fails_without_a_cache_path(): void
    {
        $exit = Artisan::call('sdo:warm', [
            'paths' => [__DIR__.'/Fixtures'],
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('No cache path configured', Artisan::output());
    }

    public function test_sdo_warm_fails_without_any_paths(): void
    {
        config(['simple-data-objects.paths' => []]);

        $exit = Artisan::call('sdo:warm', [
            '--cache' => $this->cacheDir,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('No source paths given', Artisan::output());
    }

    public function test_sdo_warm_warms_discovered_data_classes(): void
    {
        $exit = Artisan::call('sdo:warm', [
            'paths' => [__DIR__.'/Fixtures/UserData.php'],
            '--cache' => $this->cacheDir,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('warmed', Artisan::output());
        $this->assertNotEmpty(glob($this->cacheDir.'/*.meta.php'));
    }

    public function test_sdo_warm_warns_when_no_data_classes_are_found(): void
    {
        $exit = Artisan::call('sdo:warm', [
            // The abstract Testbench TestCase itself — not a BaseData subclass at all.
            'paths' => [__DIR__.'/Laravel/TestCase.php'],
            '--cache' => $this->cacheDir,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No concrete BaseData subclasses found', Artisan::output());
    }

    public function test_sdo_warm_reports_skipped_for_non_exportable_metadata(): void
    {
        $exit = Artisan::call('sdo:warm', [
            'paths' => [__DIR__.'/Fixtures/NonExportableData.php'],
            '--cache' => $this->cacheDir,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('skipped', Artisan::output());
        $this->assertEmpty(glob($this->cacheDir.'/*.meta.php'));
    }

    public function test_sdo_warm_uses_configured_paths_and_cache_when_no_options_given(): void
    {
        config([
            'simple-data-objects.paths' => [__DIR__.'/Fixtures/UserData.php'],
            'simple-data-objects.cache_path' => $this->cacheDir,
        ]);

        $exit = Artisan::call('sdo:warm');

        $this->assertSame(0, $exit);
        $this->assertNotEmpty(glob($this->cacheDir.'/*.meta.php'));
    }

    public function test_boot_applies_configured_cache_path_to_metadata_registry(): void
    {
        config(['simple-data-objects.cache_path' => $this->cacheDir]);

        // The app is already booted (config was set after boot() ran in
        // setUp) — re-run boot() on a fresh provider instance to prove the
        // wiring itself works, without relying on sdo:warm (which sets its
        // own storage path directly via CacheWarmer::warm()).
        (new SimpleDataObjectsServiceProvider($this->app))->boot();

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertNotEmpty(glob($this->cacheDir.'/*.meta.php'));
    }

    public function test_sdo_clear_succeeds(): void
    {
        $exit = Artisan::call('sdo:clear');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('cleared', Artisan::output());
    }
}
