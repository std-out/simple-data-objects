<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class MetadataCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/sdo_test_cache_'.uniqid();
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
            rmdir($this->cacheDir);
        }
    }

    public function test_cache_file_is_created_on_first_access(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $files = glob($this->cacheDir.'/*.php');
        $this->assertNotEmpty($files);
    }

    public function test_cache_file_contains_class_name_segment(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $files = glob($this->cacheDir.'/*.php');
        $this->assertStringContainsString('UserData', basename($files[0]));
    }

    public function test_cached_meta_produces_correct_instance(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        MetadataRegistry::flush();

        $user = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com']);

        $this->assertSame('Bob', $user->name);
    }

    public function test_clear_cache_removes_files(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        MetadataRegistry::clearCache();

        $files = glob($this->cacheDir.'/*.php');
        $this->assertEmpty($files);
    }

    public function test_no_cache_file_without_storage_path(): void
    {
        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $files = glob($this->cacheDir.'/*.php');
        $this->assertEmpty($files);
    }

    public function test_cached_class_meta_is_valid(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        MetadataRegistry::flush();

        $meta = MetadataRegistry::get(UserData::class);

        $this->assertNotEmpty($meta->parameters);
        $this->assertSame('name', $meta->parameters[0]->phpName);
    }
}
