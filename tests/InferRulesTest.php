<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredAddressData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredEnumData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredExplicitOverrideData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredMergeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredNestedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredNestedNoRulesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredScalarData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredTreeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredUnionAndUntypedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class InferRulesTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    public function test_scalar_types_infer_presence_and_type_rules(): void
    {
        $rules = MetadataRegistry::get(InferredScalarData::class)->validationRules;

        $this->assertSame(['required', 'string'], $rules['name']);
        $this->assertSame(['required', 'integer'], $rules['age']);
        $this->assertSame(['required', 'numeric'], $rules['balance']);
        $this->assertSame(['required', 'boolean'], $rules['active']);
        $this->assertSame(['required', 'array'], $rules['tags']);
        $this->assertSame(['nullable', 'string'], $rules['nickname']);
    }

    public function test_enum_field_infers_rule_enum_instance(): void
    {
        $rules = MetadataRegistry::get(InferredEnumData::class)->validationRules;

        $this->assertSame('required', $rules['status'][0]);
        $this->assertInstanceOf(Enum::class, $rules['status'][1]);
    }

    public function test_nested_dto_cascades_dot_notation_rules(): void
    {
        $rules = MetadataRegistry::get(InferredNestedData::class)->validationRules;

        $this->assertSame(['required', 'array'], $rules['address']);
        $this->assertSame(['required', 'string'], $rules['address.street']);
        $this->assertSame(['required', 'string'], $rules['address.city']);
        $this->assertSame(['nullable', 'string'], $rules['address.zip']);
    }

    public function test_nested_dto_without_own_rules_gets_no_cascade(): void
    {
        $rules = MetadataRegistry::get(InferredNestedNoRulesData::class)->validationRules;

        $this->assertSame(['required', 'array'], $rules['address']);
        $this->assertArrayNotHasKey('address.street', $rules);
        $this->assertArrayNotHasKey('address.city', $rules);
    }

    public function test_data_collection_cascades_star_notation_rules(): void
    {
        $rules = MetadataRegistry::get(InferredCollectionData::class)->validationRules;

        $this->assertSame(['required', 'array'], $rules['items']);
        $this->assertSame(['required', 'string'], $rules['items.*.name']);
        $this->assertSame(['required', 'numeric'], $rules['items.*.price']);
    }

    public function test_explicit_rules_replace_inferred_by_default(): void
    {
        $rules = MetadataRegistry::get(InferredExplicitOverrideData::class)->validationRules;

        $this->assertSame(['nullable', 'string'], $rules['name']);

        InferredExplicitOverrideData::validate(['name' => null]);

        $this->assertTrue(true);
    }

    public function test_explicit_rules_merge_when_requested(): void
    {
        $rules = MetadataRegistry::get(InferredMergeData::class)->validationRules;

        $this->assertSame(['required', 'string', 'max:5'], $rules['code']);

        $this->expectException(ValidationException::class);

        InferredMergeData::validate(['code' => 'toolong']);
    }

    public function test_union_type_infers_rule_from_first_builtin_member(): void
    {
        // PHP's ReflectionUnionType doesn't preserve `int|string` declaration order
        $rules = MetadataRegistry::get(InferredUnionAndUntypedData::class)->validationRules;

        $this->assertSame(['required', 'string'], $rules['identifier']);
    }

    public function test_union_type_without_builtin_member_gets_presence_only(): void
    {
        $rules = MetadataRegistry::get(InferredUnionAndUntypedData::class)->validationRules;

        $this->assertSame(['nullable'], $rules['marker']);
    }

    public function test_untyped_parameter_gets_presence_only(): void
    {
        $rules = MetadataRegistry::get(InferredUnionAndUntypedData::class)->validationRules;

        $this->assertSame(['nullable'], $rules['misc']);
    }

    public function test_class_without_inferrules_is_unaffected(): void
    {
        $rules = MetadataRegistry::get(UserData::class)->validationRules;

        $this->assertSame([], $rules);
    }

    public function test_self_referential_nested_does_not_recurse_infinitely(): void
    {
        $rules = MetadataRegistry::get(InferredTreeData::class)->validationRules;

        $this->assertSame(['required', 'string'], $rules['name']);
        $this->assertSame(['nullable', 'array'], $rules['parent']);
        $this->assertArrayNotHasKey('parent.name', $rules);
    }

    public function test_from_validated_enforces_inferred_scalar_rules(): void
    {
        $this->expectException(ValidationException::class);

        InferredScalarData::fromValidated([
            'name' => 'Ada',
            'age' => 'not-a-number',
            'balance' => 1.5,
            'active' => true,
            'tags' => [],
        ]);
    }

    public function test_from_validated_passes_with_valid_inferred_data(): void
    {
        $data = InferredScalarData::fromValidated([
            'name' => 'Ada',
            'age' => 30,
            'balance' => 1.5,
            'active' => true,
            'tags' => ['a', 'b'],
        ]);

        $this->assertSame('Ada', $data->name);
        $this->assertNull($data->nickname);
    }

    public function test_from_validated_enforces_nested_cascade_rules(): void
    {
        $this->expectException(ValidationException::class);

        InferredNestedData::fromValidated([
            'name' => 'Ada',
            'address' => [
                'street' => '12 Analytical Engine Ave',
                // 'city' missing — required by cascaded rule
            ],
        ]);
    }

    public function test_from_validated_passes_with_valid_nested_data(): void
    {
        $data = InferredNestedData::fromValidated([
            'name' => 'Ada',
            'address' => [
                'street' => '12 Analytical Engine Ave',
                'city' => 'London',
            ],
        ]);

        $this->assertInstanceOf(InferredAddressData::class, $data->address);
        $this->assertSame('London', $data->address->city);
    }
}
