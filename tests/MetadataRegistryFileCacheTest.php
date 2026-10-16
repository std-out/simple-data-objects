<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\HydratorCompiler;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\SerializerCompiler;
use StdOut\SimpleDataObjects\Tests\Fixtures\ArrayObjectDefaultData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EnumDefaultData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ObjectDefaultData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class MetadataRegistryFileCacheTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/sdo_registry_'.bin2hex(random_bytes(6));
        mkdir($this->dir);
        MetadataRegistry::setStoragePath($this->dir);
        MetadataRegistry::flush();
    }

    protected function tearDown(): void
    {
        MetadataRegistry::clearCache();
        MetadataRegistry::setStoragePath('');
        MetadataRegistry::flush();
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function cacheFile(string $class): string
    {
        return $this->dir.'/'.hash('sha256', $class).'.meta.php';
    }

    public function test_clear_cache_and_flush_drop_compiled_closures(): void
    {
        foreach ([MetadataRegistry::clearCache(...), MetadataRegistry::flush(...)] as $reset) {
            UserData::from(['name' => 'a', 'email' => 'a@b.c'])->toArray();

            $reset();

            $this->assertSame([], HydratorCompiler::$hydrators);
            $this->assertSame([], SerializerCompiler::$serializers);
        }
    }

    public function test_clear_cache_removes_cache_and_leftover_temp_files_only(): void
    {
        $own = ['a.meta.php', 'a.meta.php.tmp.0123456789abcdef'];
        $foreign = ['a.meta.php.bak', 'upload.tmp.1', 'notes.php'];

        foreach ([...$own, ...$foreign] as $name) {
            touch($this->dir.'/'.$name);
        }

        MetadataRegistry::clearCache();

        $this->assertSame($foreign, array_values(array_filter([...$own, ...$foreign], fn (string $name): bool => is_file($this->dir.'/'.$name))));
    }

    public function test_a_warm_cache_file_is_reused_rather_than_rebuilt(): void
    {
        foreach ([UserData::class, NoConstructorData::class, HybridData::class] as $class) {
            MetadataRegistry::get($class);
            file_put_contents($this->cacheFile($class), "\n// marker\n", FILE_APPEND);
            MetadataRegistry::flush();

            MetadataRegistry::get($class);

            $this->assertStringEndsWith("// marker\n", (string) file_get_contents($this->cacheFile($class)));
            $this->assertArrayHasKey($class, HydratorCompiler::$hydrators);
        }

        $this->assertSame('7', NoConstructorData::from(['id' => '7', 'required' => 'r'])->id);
        $this->assertSame('x', HybridData::from(['id' => 'n', 'extraId' => 'x', 'extra_label' => 'l'])->extraId);
        $this->assertSame('a', UserData::from(['name' => 'a', 'email' => 'a@b.c'])->name);
    }

    public function test_a_legacy_meta_only_cache_file_is_reused(): void
    {
        MetadataRegistry::get(UserData::class);
        $file = $this->cacheFile(UserData::class);
        $legacy = "<?php\n\nreturn ".var_export(MetadataRegistry::get(UserData::class), true).";\n// marker\n";
        file_put_contents($file, $legacy);
        MetadataRegistry::flush();

        $this->assertSame('a', UserData::from(['name' => 'a', 'email' => 'a@b.c'])->name);
        $this->assertSame($legacy, file_get_contents($file));
    }

    public function test_missing_storage_directory_is_created_with_0755(): void
    {
        $nested = $this->dir.'/a/b';
        MetadataRegistry::setStoragePath($nested);
        $umask = umask(0022);

        try {
            MetadataRegistry::get(UserData::class);
        } finally {
            umask($umask);
        }

        $this->assertSame(0755, fileperms($nested) & 0777);
        $this->assertTrue(MetadataRegistry::isPersisted(UserData::class));
    }

    public function test_storage_path_that_cannot_be_created_is_skipped(): void
    {
        touch($this->dir.'/file');
        MetadataRegistry::setStoragePath($this->dir.'/file/sub');

        $this->assertSame('a', UserData::from(['name' => 'a', 'email' => 'a@b.c'])->name);
        $this->assertFalse(MetadataRegistry::isPersisted(UserData::class));
    }

    public function test_only_exportable_default_values_are_persisted(): void
    {
        MetadataRegistry::get(EnumDefaultData::class);
        MetadataRegistry::get(ObjectDefaultData::class);
        MetadataRegistry::get(ArrayObjectDefaultData::class);

        $this->assertTrue(MetadataRegistry::isPersisted(EnumDefaultData::class));
        $this->assertFalse(MetadataRegistry::isPersisted(ObjectDefaultData::class));
        $this->assertFalse(MetadataRegistry::isPersisted(ArrayObjectDefaultData::class));
    }
}
