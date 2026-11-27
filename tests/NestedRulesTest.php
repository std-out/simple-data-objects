<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\NestedRulesParentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NestedRulesTreeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NestedWithoutRulesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class NestedRulesTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    public function test_rules_of_a_required_nested_class_are_part_of_the_parent_rules(): void
    {
        $rules = MetadataRegistry::get(NestedRulesParentData::class)->validationRules;

        $this->assertSame(['required', 'string', 'min:5'], $rules['required.name']);
        $this->assertSame(['required', 'string', 'min:5'], $rules['items.*.name']);
        $this->assertArrayNotHasKey('nullable.name', $rules);
        $this->assertArrayNotHasKey('optional.name', $rules);
        $this->assertArrayNotHasKey('nullableItems.*.name', $rules);
        $this->assertArrayNotHasKey('defaulted.name', $rules);
    }

    public function test_only_classes_with_nested_data_cascade(): void
    {
        $this->assertTrue(MetadataRegistry::get(NestedRulesParentData::class)->cascadesNestedRules);
        $this->assertFalse(MetadataRegistry::get(UserData::class)->cascadesNestedRules);
    }

    public function test_absent_or_null_optional_nested_values_are_not_validated(): void
    {
        $data = NestedRulesParentData::fromValidated([
            'required' => ['name' => 'Alice'],
            'items' => [],
            'nullable' => null,
        ]);

        $this->assertNull($data->nullable);
        $this->assertNull($data->nullableItems);
        $this->assertSame('Alice', $data->defaulted->name);
    }

    /** @return iterable<string, array{array<string, mixed>, list<string>}> */
    public static function invalidNestedInput(): iterable
    {
        $valid = ['required' => ['name' => 'Alice'], 'items' => []];

        yield 'required' => [['required' => ['name' => 'ab'], 'items' => []], ['required.name']];
        yield 'collection item' => [[...$valid, 'items' => [['name' => 'Alice'], ['name' => 'ab']]], ['items.1.name']];
        yield 'present nullable' => [[...$valid, 'nullable' => ['name' => 'ab']], ['nullable.name']];
        yield 'present optional' => [[...$valid, 'optional' => []], ['optional.name']];
        yield 'present defaulted' => [[...$valid, 'defaulted' => ['name' => 'ab']], ['defaulted.name']];
        yield 'present nullable collection item' => [[...$valid, 'nullableItems' => [['name' => 'ab']]], ['nullableItems.0.name']];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $errorKeys
     */
    #[DataProvider('invalidNestedInput')]
    public function test_nested_rules_apply_without_infer_rules(array $input, array $errorKeys): void
    {
        try {
            NestedRulesParentData::fromValidated($input);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame($errorKeys, array_keys($e->errors()));
        }

        $this->assertSame($errorKeys, array_keys(NestedRulesParentData::fromValidatedResult($input)->errors()));
    }

    public function test_nested_rules_apply_at_every_depth_of_a_self_referential_class(): void
    {
        $input = ['name' => 'leaf', 'parent' => ['name' => 'mid', 'parent' => ['name' => 'r']]];

        try {
            NestedRulesTreeData::validate($input);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['parent.parent.name'], array_keys($e->errors()));
        }
    }

    public function test_nested_discriminated_value_is_validated_against_the_concrete_class_rules(): void
    {
        try {
            OrderWithPaymentData::fromValidated(['id' => 'o-1', 'payment' => ['type' => 'card', 'amount' => 1, 'last4' => '1']]);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['payment.last4'], array_keys($e->errors()));
        }
    }

    public function test_nested_classes_without_rules_skip_validation(): void
    {
        $input = ['user' => ['name' => 'Alice', 'email' => 'alice@example.com']];

        NestedWithoutRulesData::validate($input);

        $this->assertSame('Alice', NestedWithoutRulesData::fromValidated($input)->user->name);
        $this->assertTrue(NestedWithoutRulesData::fromValidatedResult($input)->ok());
    }

    public function test_validate_on_a_class_without_rules_ignores_input_it_could_not_normalize(): void
    {
        UserData::validate(42);

        $this->addToAssertionCount(1);
    }
}
