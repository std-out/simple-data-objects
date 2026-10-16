<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\SerializerCompiler;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextHiddenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextNestedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NullableFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalIgnoredData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class SerializerCompilerTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    private static function nested(): ContextNestedData
    {
        return new ContextNestedData('label', new ContextHiddenData('Alice', 'note', 'secret'));
    }

    public function test_undeclared_contexts_share_one_compiled_serializer_and_the_cache_is_bounded(): void
    {
        $data = self::nested();

        for ($i = 0; $i < 500; $i++) {
            $this->assertSame(['label' => 'label', 'detail' => ['name' => 'Alice']], $data->toArray("context-{$i}"));
        }

        $this->assertCount(64, SerializerCompiler::$contextualSerializers[ContextNestedData::class]);
        $this->assertCount(1, array_unique(array_map(spl_object_id(...), SerializerCompiler::$contextualSerializers[ContextNestedData::class])));
    }

    public function test_undeclared_context_is_still_passed_to_nested_objects(): void
    {
        $data = self::nested();

        for ($i = 0; $i < 100; $i++) {
            $data->toArray("context-{$i}");
        }

        $this->assertSame(['label' => 'label', 'detail' => ['name' => 'Alice', 'internalNote' => 'note']], $data->toArray('admin'));
        $this->assertArrayNotHasKey('admin', SerializerCompiler::$contextualSerializers[ContextNestedData::class]);
    }

    public function test_declared_contexts_are_cached_even_beyond_the_bound(): void
    {
        $data = new ContextHiddenData('Alice', 'note', 'secret');

        for ($i = 0; $i < 100; $i++) {
            $data->toArray("context-{$i}");
        }

        $this->assertSame(['name' => 'Alice', 'internalNote' => 'note'], $data->toArray('admin'));
        $this->assertArrayHasKey('admin', SerializerCompiler::$contextualSerializers[ContextHiddenData::class]);
        $this->assertNotSame(
            SerializerCompiler::$contextualSerializers[ContextHiddenData::class]['admin'],
            SerializerCompiler::$contextualSerializers[ContextHiddenData::class]['context-0'],
        );
    }

    public function test_compiling_the_default_serializer_twice_returns_the_cached_closure(): void
    {
        $first = SerializerCompiler::compile(UserData::class);

        $this->assertSame($first, SerializerCompiler::compile(UserData::class));
        $this->assertSame($first, SerializerCompiler::$serializers[UserData::class]);
    }

    public function test_flatten_of_a_non_data_value_keeps_it_under_its_key_in_every_context(): void
    {
        $data = new NullableFlattenData('Alice');

        $this->assertSame(['name' => 'Alice', 'address' => null], $data->toArray());
        $this->assertSame(['name' => 'Alice', 'address' => null], $data->toArray('admin'));
    }

    public function test_ignore_if_null_on_an_optional_field_drops_both_null_and_missing(): void
    {
        $this->assertSame(['id' => 1], OptionalIgnoredData::from(['id' => 1])->toArray());
        $this->assertSame(['id' => 1], OptionalIgnoredData::from(['id' => 1, 'bio' => null])->toArray());
        $this->assertSame(['id' => 1, 'bio' => 'x'], OptionalIgnoredData::from(['id' => 1, 'bio' => 'x'])->toArray());
    }
}
