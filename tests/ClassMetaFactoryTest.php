<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictCollectionCastData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictCollectionFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictFlattenCastData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmptyData;
use StdOut\SimpleDataObjects\Tests\Fixtures\FlattenNonDtoData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorAttributesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorNestedData;
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

    public function test_class_without_constructor_hydrates_declared_properties(): void
    {
        $data = NoConstructorData::from(['required' => 'r', 'id' => 'abc', 'priority' => 9]);

        $this->assertSame('r', $data->required);
        $this->assertSame('abc', $data->id);
        $this->assertSame(9, $data->priority);
        $this->assertNull($data->optional);
    }

    public function test_class_without_constructor_applies_declared_default(): void
    {
        $data = NoConstructorData::from(['required' => 'r', 'id' => 'abc']);

        $this->assertSame(1, $data->priority);
    }

    public function test_class_without_constructor_throws_on_missing_required_field(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'required'/");

        NoConstructorData::from(['id' => 'abc']);
    }

    public function test_class_without_constructor_round_trips_to_array(): void
    {
        $data = NoConstructorData::from(['required' => 'r', 'id' => 'abc', 'priority' => 9]);

        $this->assertSame(
            ['optional' => null, 'required' => 'r', 'id' => 'abc', 'priority' => 9],
            $data->toArray(),
        );
    }

    public function test_class_without_constructor_excludes_static_private_and_untyped_properties(): void
    {
        $data = NoConstructorData::from(['required' => 'r', 'id' => 'abc']);

        $this->assertArrayNotHasKey('ignoredStatic', $data->toArray());
        $this->assertArrayNotHasKey('secret', $data->toArray());
        $this->assertArrayNotHasKey('untyped', $data->toArray());
        $this->assertSame('hidden', $data->secret());
    }

    public function test_class_without_constructor_supports_cast_map_hidden_ignoreifnull_and_enum(): void
    {
        $data = NoConstructorAttributesData::from([
            'name' => '  ADA  ',
            'user_id' => 42,
            'password' => 'secret',
            'status' => 'active',
        ]);

        $this->assertSame('ada', $data->name);
        $this->assertSame(42, $data->userId);
        $this->assertSame('secret', $data->password);
        $this->assertSame(Status::Active, $data->status);

        $this->assertSame(
            ['name' => 'ada', 'user_id' => 42, 'status' => 'active'],
            $data->toArray(),
        );
    }

    public function test_class_without_constructor_supports_nested_data_object_property(): void
    {
        $data = NoConstructorNestedData::from([
            'name' => 'Ada',
            'address' => ['street' => '1 Main St', 'city' => 'Kyiv'],
        ]);

        $this->assertSame('1 Main St', $data->address->street);
        $this->assertSame(
            ['name' => 'Ada', 'address' => ['street' => '1 Main St', 'city' => 'Kyiv', 'zip' => null]],
            $data->toArray(),
        );
    }

    public function test_class_without_constructor_supports_flatten(): void
    {
        $data = NoConstructorFlattenData::from(['name' => 'Ada', 'street' => '1 Main St', 'city' => 'Kyiv']);

        $this->assertSame('1 Main St', $data->address->street);
        $this->assertSame(
            ['name' => 'Ada', 'street' => '1 Main St', 'city' => 'Kyiv', 'zip' => null],
            $data->toArray(),
        );
    }

    public function test_class_without_constructor_supports_data_collection(): void
    {
        $data = NoConstructorCollectionData::from([
            'name' => 'Team',
            'members' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com'],
            ],
        ]);

        $this->assertCount(2, $data->members);
        $this->assertSame('Alice', $data->members[0]->name);
    }

    public function test_hybrid_class_hydrates_constructor_and_extra_properties(): void
    {
        $data = HybridData::from([
            'id' => '1',
            'status' => 'active',
            'note' => 'hi',
            'extraId' => 'e1',
            'extra_label' => 'Label',
        ]);

        $this->assertSame('1', $data->id);
        $this->assertSame('active', $data->status);
        $this->assertSame('hi', $data->note);
        $this->assertSame('e1', $data->extraId);
        $this->assertSame('Label', $data->extraLabel);
    }

    public function test_hybrid_class_applies_constructor_default_and_extra_default(): void
    {
        $data = HybridData::from(['id' => '1', 'extraId' => 'e1', 'extra_label' => 'Label']);

        $this->assertSame('new', $data->status);
        $this->assertNull($data->note);
    }

    public function test_hybrid_class_throws_on_missing_required_extra_property(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'extraId'/");

        HybridData::from(['id' => '1', 'extra_label' => 'Label']);
    }

    public function test_hybrid_class_round_trips_to_array_with_mapped_extra_name(): void
    {
        $data = HybridData::from(['id' => '1', 'extraId' => 'e1', 'extra_label' => 'Label']);

        $this->assertSame(
            ['id' => '1', 'status' => 'new', 'note' => null, 'extraId' => 'e1', 'extra_label' => 'Label'],
            $data->toArray(),
        );
    }
}
