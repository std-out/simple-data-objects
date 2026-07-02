<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Support\ClassMeta;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\ParameterMeta;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NonExportableData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProductData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SecretData;
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

        $this->removeDir($this->cacheDir);
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDir($entry) : unlink($entry);
        }

        rmdir($dir);
    }

    public function test_cache_file_is_created_on_first_access(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $files = glob($this->cacheDir.'/*.php');
        $this->assertNotEmpty($files);
    }

    public function test_cache_file_is_named_by_class_hash(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $files = glob($this->cacheDir.'/*.php');
        $expected = hash('sha256', UserData::class).'.meta.php';
        $this->assertSame($expected, basename($files[0]));
    }

    public function test_clear_cache_only_removes_own_meta_files(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        $foreign = $this->cacheDir.'/foreign.php';
        file_put_contents($foreign, '<?php return 1;');

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        MetadataRegistry::clearCache();

        $this->assertFileExists($foreign);
        $this->assertEmpty(glob($this->cacheDir.'/*.meta.php'));
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

    public function test_clear_cache_is_no_op_when_storage_path_not_set(): void
    {
        MetadataRegistry::flush();
        MetadataRegistry::setStoragePath('');

        // Should not throw even when storagePath is null
        MetadataRegistry::clearCache();

        $this->assertTrue(true);
    }

    public function test_persist_creates_subdirectory_if_not_exists(): void
    {
        $subDir = $this->cacheDir.'/nested/cache';
        MetadataRegistry::setStoragePath($subDir);

        UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertTrue(is_dir($subDir));
        $files = glob($subDir.'/*.php');
        $this->assertNotEmpty($files);
    }

    public function test_cache_restores_primitive_casts_via_set_state(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        ProductData::from(['sku' => 'abc', 'quantity' => '5', 'price' => '9.99', 'available' => '1', 'meta' => '{}']);
        MetadataRegistry::flush();

        $product = ProductData::from(['sku' => 'abc', 'quantity' => '5', 'price' => '9.99', 'available' => '1', 'meta' => '{}']);

        $this->assertSame(5, $product->quantity);
        $this->assertSame('abc', $product->sku);
    }

    public function test_cache_restores_datetime_casts_via_set_state(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        EventData::from(['name' => 'Conf', 'startsAt' => '2024-06-01']);
        MetadataRegistry::flush();

        $event = EventData::from(['name' => 'Conf', 'startsAt' => '2024-06-01']);

        $this->assertSame('Conf', $event->name);
        $this->assertSame('2024-06-01', $event->startsAt->format('Y-m-d'));
    }

    public function test_cache_restores_enum_cast_via_set_state(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        PaymentData::from(['amount' => 100, 'status' => 'active']);
        MetadataRegistry::flush();

        $payment = PaymentData::from(['amount' => 100, 'status' => 'active']);

        $this->assertSame(100, $payment->amount);
    }

    public function test_encrypted_cast_is_never_persisted_to_disk(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        $cast = new EncryptedCast('test-encryption-key');
        $encrypted = $cast->set('secret');

        SecretData::from(['token' => $encrypted]);

        // Key material must never be written to the file cache
        $this->assertEmpty(glob($this->cacheDir.'/*.meta.php'));

        // Hydration still works via the in-memory metadata cache
        MetadataRegistry::flush();
        $secret = SecretData::from(['token' => $encrypted]);

        $this->assertSame('secret', $secret->token);
    }

    public function test_non_exportable_cast_skips_file_persistence(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        NonExportableData::from(['value' => 'test']);

        $files = glob($this->cacheDir.'/*.php');
        $this->assertEmpty($files);
    }

    public function test_non_exportable_cast_still_works_without_cache(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);

        $data = NonExportableData::from(['value' => 'test']);

        $this->assertSame('test', $data->value);
    }

    public function test_isexportable_returns_false_for_non_string_rule(): void
    {
        $param = new ParameterMeta(
            phpName: 'field',
            inputName: 'field',
            allowsNull: false,
            hasDefault: false,
            defaultValue: null,
            nestedDataClass: null,
            enumClass: null,
            dataCollectionClass: null,
            isHidden: false,
            ignoreIfNull: false,
            flatten: false,
            rules: [new \stdClass],
            caster: null,
        );
        $meta = new ClassMeta([$param]);

        $ref = new \ReflectionClass(MetadataRegistry::class);
        $method = $ref->getMethod('isExportable');

        $this->assertFalse($method->invoke(null, $meta));
    }

    public function test_persist_silently_skips_when_file_put_contents_fails(): void
    {
        // Use a regular FILE as the storage path so mkdir and file_put_contents
        // both fail. We suppress the expected PHP warnings because we are
        // deliberately testing the failure path — not hiding a real bug.
        $fakePath = sys_get_temp_dir().'/sdo_fake_dir_'.uniqid();
        file_put_contents($fakePath, 'not-a-dir');

        MetadataRegistry::setStoragePath($fakePath);
        MetadataRegistry::flush();

        set_error_handler(static fn () => true);
        try {
            UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue(true);

        unlink($fakePath);
        MetadataRegistry::setStoragePath('');
    }

    public function test_persist_skips_when_tmp_file_cannot_be_written(): void
    {
        // Pre-create a DIRECTORY at the deterministic tmp path so
        // file_put_contents fails even when tests run as root (read-only
        // permissions would not stop root), covering the failure branch.
        $tmpPath = $this->cacheDir.'/'.hash('sha256', UserData::class).'.meta.php.tmp.'.getmypid();
        mkdir($tmpPath, 0755, true);

        MetadataRegistry::setStoragePath($this->cacheDir);
        MetadataRegistry::flush();

        set_error_handler(static fn () => true);
        try {
            UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        } finally {
            restore_error_handler();
        }

        $this->assertEmpty(glob($this->cacheDir.'/*.meta.php'));

        rmdir($tmpPath);
    }

    public function test_persist_unlinks_tmp_when_rename_fails(): void
    {
        MetadataRegistry::setStoragePath($this->cacheDir);
        MetadataRegistry::flush();

        // Pre-create the target cache file as a directory so rename fails.
        // We suppress the expected PHP warning for the same reason as above.
        $targetFile = $this->cacheDir.'/'.hash('sha256', UserData::class).'.meta.php';
        mkdir($targetFile, 0755, true);

        set_error_handler(static fn () => true);
        try {
            UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        } finally {
            restore_error_handler();
        }

        $this->assertTrue(is_dir($targetFile));

        $tmpFiles = glob($this->cacheDir.'/*.tmp.*');
        $this->assertEmpty($tmpFiles);

        rmdir($targetFile);
    }

    public function test_encrypted_cast_refuses_serialization(): void
    {
        $this->expectException(\LogicException::class);

        serialize(new EncryptedCast('direct-test-key'));
    }

    public function test_encrypted_cast_redacts_debug_output(): void
    {
        ob_start();
        var_dump(new EncryptedCast('direct-test-key'));
        $dump = (string) ob_get_clean();

        $this->assertStringContainsString('[redacted]', $dump);
        $this->assertStringNotContainsString('direct-test-key', $dump);
    }
}
