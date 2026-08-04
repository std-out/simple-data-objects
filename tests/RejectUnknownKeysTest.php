<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\ClassMetaFactory;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\RejectUnknownKeysDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\RejectUnknownKeysFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictAliasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictHybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictNoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictSnakeCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictUserData;

class RejectUnknownKeysTest extends TestCase
{
    public function test_accepts_input_with_only_known_keys(): void
    {
        $user = StrictUserData::from(['name' => 'Alice', 'user_email' => 'alice@example.com']);

        $this->assertSame('Alice', $user->name);
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_accepts_input_omitting_an_optional_key(): void
    {
        $user = StrictUserData::from(['name' => 'Alice']);

        $this->assertSame('Alice', $user->name);
        $this->assertNull($user->email);
    }

    public function test_rejects_a_single_unknown_key(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown key ['extra'] for StdOut\SimpleDataObjects\Tests\Fixtures\StrictUserData.");

        StrictUserData::from(['name' => 'Alice', 'extra' => 'oops']);
    }

    public function test_rejects_multiple_unknown_keys(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown keys ['extra', 'another'] for StdOut\SimpleDataObjects\Tests\Fixtures\StrictUserData.");

        StrictUserData::from(['name' => 'Alice', 'extra' => 'oops', 'another' => 1]);
    }

    public function test_exception_exposes_the_unknown_keys_as_a_list(): void
    {
        try {
            StrictUserData::from(['name' => 'Alice', 'extra' => 'oops', 'another' => 1]);
            $this->fail('Expected DataHydrationException was not thrown.');
        } catch (DataHydrationException $e) {
            $this->assertSame(['extra', 'another'], $e->unknownKeys);
        }
    }

    public function test_other_exceptions_have_an_empty_unknown_keys_list(): void
    {
        try {
            StrictUserData::from([]);
            $this->fail('Expected DataHydrationException was not thrown.');
        } catch (DataHydrationException $e) {
            $this->assertStringContainsString("Missing required field 'name'", $e->getMessage());
            $this->assertSame([], $e->unknownKeys);
        }
    }

    public function test_the_mapped_input_key_is_known_not_the_php_property_name(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown key ['email'] for StdOut\SimpleDataObjects\Tests\Fixtures\StrictUserData.");

        // The property is named $email but #[MapPropertyName('user_email')]
        // means the raw PHP name is not a recognized input key.
        StrictUserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
    }

    public function test_try_from_returns_null_for_unknown_keys(): void
    {
        $this->assertNull(StrictUserData::tryFrom(['name' => 'Alice', 'extra' => 'oops']));
    }

    public function test_round_trip_from_to_array(): void
    {
        $original = StrictUserData::from(['name' => 'Alice', 'user_email' => 'alice@example.com']);
        $restored = StrictUserData::from($original->toArray());

        $this->assertTrue($original->equals($restored));
    }

    public function test_every_alias_of_a_mapped_property_is_a_known_key(): void
    {
        $this->assertSame(1, StrictAliasedData::from(['user_id' => 1])->userId);
        $this->assertSame(2, StrictAliasedData::from(['uid' => 2])->userId);
    }

    public function test_rejects_an_unmapped_key_even_with_aliases_declared(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Unknown key \['userId'\]/");

        StrictAliasedData::from(['userId' => 1]);
    }

    public function test_collection_rejects_unknown_keys_per_item(): void
    {
        $this->expectException(DataHydrationException::class);

        StrictUserData::collection([
            ['name' => 'Alice'],
            ['name' => 'Bob', 'extra' => 'oops'],
        ]);
    }

    public function test_constructor_less_class_rejects_unknown_keys(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown key ['nope'] for StdOut\SimpleDataObjects\Tests\Fixtures\StrictNoConstructorData.");

        StrictNoConstructorData::from(['name' => 'Bob', 'nope' => 1]);
    }

    public function test_constructor_less_class_accepts_known_keys(): void
    {
        $data = StrictNoConstructorData::from(['name' => 'Bob', 'email' => 'bob@example.com']);

        $this->assertSame('Bob', $data->name);
        $this->assertSame('bob@example.com', $data->email);
    }

    public function test_hybrid_class_rejects_unknown_keys(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown key ['nope'] for StdOut\SimpleDataObjects\Tests\Fixtures\StrictHybridData.");

        StrictHybridData::from(['id' => '1', 'note' => 'hi', 'nope' => 1]);
    }

    public function test_hybrid_class_accepts_known_keys(): void
    {
        $data = StrictHybridData::from(['id' => '1', 'note' => 'hi']);

        $this->assertSame('1', $data->id);
        $this->assertSame('hi', $data->note);
    }

    public function test_hybrid_class_via_from_lazy_rejects_unknown_keys_on_first_access(): void
    {
        $data = StrictHybridData::fromLazy(['id' => '1', 'nope' => 1]);

        $this->expectException(DataHydrationException::class);

        $this->assertSame('1', $data->id);
    }

    public function test_transform_keys_input_uses_the_transformed_name(): void
    {
        $data = StrictSnakeCasedData::from(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $this->assertSame('Ada', $data->firstName);
    }

    public function test_transform_keys_rejects_the_untransformed_php_name(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown key ['firstName'] for StdOut\SimpleDataObjects\Tests\Fixtures\StrictSnakeCasedData.");

        StrictSnakeCasedData::from(['firstName' => 'Ada', 'last_name' => 'Lovelace']);
    }

    public function test_from_lazy_defers_the_check_until_first_property_access(): void
    {
        $user = StrictUserData::fromLazy(['name' => 'Alice', 'extra' => 'oops']); // no throw yet

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Unknown key \['extra'\]/");

        $this->assertSame('Alice', $user->name);
    }

    public function test_combining_with_discriminator_is_rejected_at_metadata_build_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/#\[RejectUnknownKeys\] and #\[Discriminator\] cannot be combined/');

        ClassMetaFactory::build(RejectUnknownKeysDiscriminatorData::class);
    }

    public function test_combining_with_flatten_is_rejected_at_metadata_build_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/#\[RejectUnknownKeys\] and #\[Flatten\] cannot be combined/');

        ClassMetaFactory::build(RejectUnknownKeysFlattenData::class);
    }

    public function test_flag_survives_metadata_cache_round_trip(): void
    {
        $cacheDir = sys_get_temp_dir().'/sdo_reject_unknown_keys_cache_'.uniqid();
        mkdir($cacheDir, 0755, true);

        try {
            MetadataRegistry::flush();
            MetadataRegistry::setStoragePath($cacheDir);

            StrictUserData::from(['name' => 'Alice']);
            $this->assertTrue(MetadataRegistry::isPersisted(StrictUserData::class));

            // Simulate a fresh process: in-memory caches gone, files remain
            MetadataRegistry::flush();

            $this->expectException(DataHydrationException::class);
            StrictUserData::from(['name' => 'Alice', 'extra' => 'oops']);
        } finally {
            MetadataRegistry::clearCache();
            MetadataRegistry::setStoragePath('');
            MetadataRegistry::flush();

            foreach (glob($cacheDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($cacheDir);
        }
    }
}
