<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\CacheWarmer;
use StdOut\SimpleDataObjects\Support\HydratorCompiler;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\SecretData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class CacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/sdo_warm_'.uniqid();
        mkdir($this->cacheDir, 0755, true);
        MetadataRegistry::flush();
    }

    protected function tearDown(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);
        MetadataRegistry::clearCache();
        MetadataRegistry::setStoragePath('');
        MetadataRegistry::flush();

        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->cacheDir);
        }
    }

    public function test_discover_finds_data_classes_in_directory(): void
    {
        $classes = CacheWarmer::discover([__DIR__.'/Fixtures']);

        $this->assertContains(UserData::class, $classes);
        $this->assertContains(SecretData::class, $classes);
        // Enums, casts, and pipes in the fixtures dir are not BaseData subclasses
        $this->assertNotContains(Fixtures\Status::class, $classes);
    }

    public function test_discover_accepts_a_single_file(): void
    {
        $classes = CacheWarmer::discover([__DIR__.'/Fixtures/UserData.php']);

        $this->assertSame([UserData::class], $classes);
    }

    public function test_discover_ignores_missing_paths(): void
    {
        $this->assertSame([], CacheWarmer::discover([__DIR__.'/does-not-exist']));
    }

    public function test_paths_from_composer_reads_psr4_directories(): void
    {
        $composer = $this->cacheDir.'/composer.json';
        file_put_contents($composer, json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Modules\\' => ['modules/a', 'modules/b'],
                ],
            ],
        ]));

        $this->assertSame(
            [$this->cacheDir.'/app', $this->cacheDir.'/modules/a', $this->cacheDir.'/modules/b'],
            CacheWarmer::pathsFromComposer($composer),
        );
    }

    public function test_paths_from_composer_handles_missing_or_invalid_file(): void
    {
        $this->assertSame([], CacheWarmer::pathsFromComposer($this->cacheDir.'/nope/composer.json'));

        $broken = $this->cacheDir.'/composer.json';
        file_put_contents($broken, 'not-json');

        $this->assertSame([], CacheWarmer::pathsFromComposer($broken));
    }

    public function test_discover_tolerates_truncated_class_keyword(): void
    {
        // `class` as the last significant token: both tokenizer lookups
        // (previous and next significant) run off the ends of the file
        $file = $this->cacheDir.'/truncated.php';
        file_put_contents($file, '<?php class');

        $this->assertSame([], CacheWarmer::discover([$file]));
    }

    public function test_warm_writes_cache_files_and_reports_skips(): void
    {
        $result = CacheWarmer::warm($this->cacheDir, [
            __DIR__.'/Fixtures/UserData.php',
            __DIR__.'/Fixtures/SecretData.php',
        ]);

        $this->assertSame([UserData::class], $result['warmed']);
        // EncryptedCast metadata must never reach disk
        $this->assertSame([SecretData::class], $result['skipped']);
        $this->assertNotEmpty(glob($this->cacheDir.'/*.meta.php'));

        // A warmed cache restores compiled closures without reflection or eval
        MetadataRegistry::flush();
        MetadataRegistry::setStoragePath($this->cacheDir);
        MetadataRegistry::get(UserData::class);

        $this->assertArrayHasKey(UserData::class, HydratorCompiler::$hydrators);
    }

    public function test_warm_fails_fast_on_invalid_data_class(): void
    {
        // Deploy-time warming should surface broken DTO definitions immediately,
        // naming the offending class
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot warm .*ConflictCollectionCastData: .*cannot be combined/');

        CacheWarmer::warm($this->cacheDir, [__DIR__.'/Fixtures/ConflictCollectionCastData.php']);
    }
}
