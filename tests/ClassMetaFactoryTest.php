<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictCollectionCastData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictCollectionFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictFlattenCastData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmptyData;
use StdOut\SimpleDataObjects\Tests\Fixtures\FlattenNonDtoData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\UnionTypeData;

class ClassMetaFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    public function test_datacollection_and_cast_cannot_be_combined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be combined/');

        ConflictCollectionCastData::from(['items' => []]);
    }

    public function test_datacollection_and_flatten_cannot_be_combined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be combined/');

        ConflictCollectionFlattenData::from(['items' => []]);
    }

    public function test_flatten_and_cast_cannot_be_combined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be combined/');

        ConflictFlattenCastData::from(['user' => ['name' => 'Alice', 'email' => 'a@example.com']]);
    }

    public function test_flatten_requires_nested_data_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/requires a nested BaseData type/');

        FlattenNonDtoData::from(['name' => 'Alice']);
    }

    public function test_union_type_property_resolves_enum(): void
    {
        $data = UnionTypeData::from(['name' => 'Alice', 'status' => 'active']);

        $this->assertSame('Alice', $data->name);
        $this->assertSame(Status::Active, $data->status);
    }

    public function test_union_type_property_allows_null(): void
    {
        $data = UnionTypeData::from(['name' => 'Alice']);

        $this->assertNull($data->status);
    }

    public function test_class_without_constructor_hydrates_with_no_args(): void
    {
        $data = EmptyData::from([]);

        $this->assertInstanceOf(EmptyData::class, $data);
        $this->assertSame([], $data->toArray());
    }
}
