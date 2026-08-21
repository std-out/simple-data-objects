<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Optional;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredOptionalData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalFieldData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalNestedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalWithDefaultData;
use StdOut\SimpleDataObjects\Tests\Fixtures\WhenLoadedOptionalData;

class OptionalTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    public function test_is_missing_helper_and_singleton(): void
    {
        $this->assertTrue(Optional::isMissing(Optional::missing()));
        $this->assertFalse(Optional::isMissing(null));
        $this->assertFalse(Optional::isMissing('x'));
        $this->assertSame(Optional::missing(), Optional::missing());
    }

    public function test_missing_key_resolves_to_optional_missing(): void
    {
        $data = OptionalFieldData::from(['id' => 1]);

        $this->assertTrue(Optional::isMissing($data->name));
        $this->assertTrue(Optional::isMissing($data->bio));
    }

    public function test_present_value_hydrates_normally(): void
    {
        $data = OptionalFieldData::from(['id' => 1, 'name' => 'Alice', 'bio' => 'hi']);

        $this->assertSame('Alice', $data->name);
        $this->assertSame('hi', $data->bio);
    }

    public function test_present_null_is_allowed_for_the_nullable_optional_field(): void
    {
        $data = OptionalFieldData::from(['id' => 1, 'name' => 'Alice', 'bio' => null]);

        $this->assertNull($data->bio);
    }

    public function test_present_null_throws_for_the_required_optional_field(): void
    {
        $this->expectException(\TypeError::class);

        OptionalFieldData::from(['id' => 1, 'name' => null, 'bio' => null]);
    }

    public function test_to_array_omits_missing_optional_fields(): void
    {
        $data = OptionalFieldData::from(['id' => 1]);

        $this->assertSame(['id' => 1], $data->toArray());
    }

    public function test_to_array_includes_explicit_null_and_present_values(): void
    {
        $data = OptionalFieldData::from(['id' => 1, 'name' => 'Alice', 'bio' => null]);

        $this->assertSame(['id' => 1, 'name' => 'Alice', 'bio' => null], $data->toArray());
    }

    public function test_roundtrip_through_to_array_preserves_missing(): void
    {
        $data = OptionalFieldData::from(['id' => 1]);
        $rehydrated = OptionalFieldData::from($data->toArray());

        $this->assertTrue(Optional::isMissing($rehydrated->name));
    }

    public function test_defined_only_matches_to_array(): void
    {
        $data = OptionalFieldData::from(['id' => 1, 'name' => 'Alice']);

        $this->assertSame($data->toArray(), $data->definedOnly());
    }

    public function test_with_copies_a_missing_field_through_unchanged(): void
    {
        $data = OptionalFieldData::from(['id' => 1]);
        $copy = $data->with(id: 2);

        $this->assertTrue(Optional::isMissing($copy->name));
    }

    public function test_with_can_explicitly_reset_a_field_to_missing(): void
    {
        $data = OptionalFieldData::from(['id' => 1, 'name' => 'Alice', 'bio' => 'hi']);
        $copy = $data->with(name: Optional::missing());

        $this->assertTrue(Optional::isMissing($copy->name));
        $this->assertSame('hi', $copy->bio);
    }

    public function test_from_result_missing_key_resolves_to_optional_missing(): void
    {
        $result = OptionalFieldData::fromResult(['id' => 1]);

        $this->assertTrue($result->ok());
        $this->assertTrue(Optional::isMissing($result->value()->name));
    }

    public function test_from_result_present_value_hydrates_normally(): void
    {
        $result = OptionalFieldData::fromResult(['id' => 1, 'name' => 'Alice', 'bio' => 'hi']);

        $this->assertTrue($result->ok());
        $this->assertSame('Alice', $result->value()->name);
    }

    public function test_nested_optional_field_missing(): void
    {
        $data = OptionalNestedData::from(['name' => 'Team']);

        $this->assertTrue(Optional::isMissing($data->address));
        $this->assertSame(['name' => 'Team'], $data->toArray());
    }

    public function test_nested_optional_field_present(): void
    {
        $data = OptionalNestedData::from(['name' => 'Team', 'address' => ['street' => '1 Ave', 'city' => 'London']]);

        $this->assertSame('London', $data->address->city);
    }

    public function test_nested_optional_field_missing_via_from_result(): void
    {
        $result = OptionalNestedData::fromResult(['name' => 'Team']);

        $this->assertTrue($result->ok());
        $this->assertTrue(Optional::isMissing($result->value()->address));
    }

    public function test_collection_optional_field_missing(): void
    {
        $data = OptionalCollectionData::from(['name' => 'Team']);

        $this->assertTrue(Optional::isMissing($data->members));
        $this->assertSame(['name' => 'Team'], $data->toArray());
    }

    public function test_collection_optional_field_present(): void
    {
        $data = OptionalCollectionData::from([
            'name' => 'Team',
            'members' => [['name' => 'A', 'email' => 'a@example.com']],
        ]);

        $this->assertCount(1, $data->members);
    }

    public function test_collection_optional_field_missing_via_from_result(): void
    {
        $result = OptionalCollectionData::fromResult(['name' => 'Team']);

        $this->assertTrue($result->ok());
        $this->assertTrue(Optional::isMissing($result->value()->members));
    }

    public function test_infer_rules_presence_is_sometimes_for_the_required_optional_field(): void
    {
        $rules = MetadataRegistry::get(InferredOptionalData::class)->validationRules;

        $this->assertSame(['sometimes', 'string'], $rules['nickname']);
    }

    public function test_infer_rules_presence_is_sometimes_and_nullable_for_the_nullable_optional_field(): void
    {
        $rules = MetadataRegistry::get(InferredOptionalData::class)->validationRules;

        $this->assertSame(['sometimes', 'nullable', 'string'], $rules['bio']);
    }

    public function test_when_loaded_unloaded_relation_resolves_to_optional_missing(): void
    {
        $model = $this->createMock(Model::class);
        $model->method('attributesToArray')->willReturn(['id' => 1]);
        $model->method('relationLoaded')->willReturn(false);

        $data = WhenLoadedOptionalData::fromModel($model);

        $this->assertTrue(Optional::isMissing($data->customer));
    }

    public function test_when_loaded_loaded_relation_hydrates_normally(): void
    {
        /** @var Model&MockObject $model */
        $model = $this->createMock(Model::class);
        $model->method('attributesToArray')->willReturn(['id' => 1]);
        $model->method('relationLoaded')->willReturn(true);
        $model->method('getRelation')->willReturn(['name' => 'Bob']);

        $data = WhenLoadedOptionalData::fromModel($model);

        $this->assertSame('Bob', $data->customer->name);
    }

    public function test_optional_with_a_default_value_throws_at_build_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Optional cannot be combined with a default value/');

        OptionalWithDefaultData::from([]);
    }

    public function test_optional_with_flatten_throws_at_build_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Optional cannot be combined/');

        OptionalFlattenData::from(['name' => 'Alice']);
    }
}
