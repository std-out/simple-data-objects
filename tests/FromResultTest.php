<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\HydratorCompiler;
use StdOut\SimpleDataObjects\Tests\Fixtures\AddressData;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\BankPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CardPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CollectingEdgeCasesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConstructAssertData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredEnumData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredNestedNoRulesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PersonData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PipedNestedFieldData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProductData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TeamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowingPipeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowingPipeHybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowingPipeNoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\WhenLoadedOrderData;

class FromResultTest extends TestCase
{
    public function test_success_returns_the_hydrated_instance(): void
    {
        $result = UserData::fromResult(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertTrue($result->ok());
        $this->assertSame('Alice', $result->value()->name);
    }

    public function test_missing_required_field_is_collected_as_an_error(): void
    {
        $result = UserData::fromResult(['name' => 'Alice']);

        $this->assertFalse($result->ok());
        $this->assertSame(
            ['email' => "Missing required field 'email' for StdOut\SimpleDataObjects\Tests\Fixtures\UserData."],
            $result->errors(),
        );
    }

    public function test_multiple_missing_fields_are_all_collected_at_once(): void
    {
        $result = UserData::fromResult([]);

        $this->assertFalse($result->ok());
        $this->assertSame(['name', 'email'], array_keys($result->errors()));
    }

    public function test_a_field_with_a_default_does_not_produce_an_error_when_missing(): void
    {
        $result = UserData::fromResult(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertTrue($result->ok());
        $this->assertNull($result->value()->phone);
    }

    public function test_invalid_enum_value_is_collected_as_an_error(): void
    {
        $result = InferredEnumData::fromResult(['status' => 'bogus']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('status', $result->errors());
    }

    public function test_invalid_custom_cast_value_is_collected_as_an_error(): void
    {
        $result = ProductData::fromResult([
            'sku' => 'ABC',
            'quantity' => '5',
            'price' => '9.99',
            'available' => '1',
            'meta' => 'not json{{{',
        ]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('meta', $result->errors());
    }

    public function test_already_hydrated_instance_passes_through(): void
    {
        $existing = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com']);
        $result = UserData::fromResult($existing);

        $this->assertTrue($result->ok());
        $this->assertSame($existing, $result->value());
    }

    public function test_json_string_input_is_normalized(): void
    {
        $result = UserData::fromResult('{"name":"Zoe","email":"zoe@example.com"}');

        $this->assertTrue($result->ok());
        $this->assertSame('Zoe', $result->value()->name);
    }

    public function test_invalid_json_string_is_collected_as_an_input_error(): void
    {
        $result = UserData::fromResult('not json{{{');

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('$input', $result->errors());
    }

    public function test_nested_data_object_errors_use_a_dot_path(): void
    {
        $result = InferredNestedNoRulesData::fromResult([
            'name' => 'Ada',
            'address' => ['street' => '1 Ave'],
        ]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('address.city', $result->errors());
    }

    public function test_nested_data_object_success(): void
    {
        $result = InferredNestedNoRulesData::fromResult([
            'name' => 'Ada',
            'address' => ['street' => '1 Ave', 'city' => 'London'],
        ]);

        $this->assertTrue($result->ok());
        $this->assertSame('London', $result->value()->address->city);
    }

    public function test_nested_data_object_accepts_an_already_hydrated_instance(): void
    {
        $address = AddressData::from(['street' => '1 Ave', 'city' => 'London']);
        $result = InferredNestedNoRulesData::fromResult(['name' => 'Ada', 'address' => $address]);

        $this->assertTrue($result->ok());
        $this->assertSame($address, $result->value()->address);
    }

    public function test_flatten_merges_nested_errors_without_a_prefix(): void
    {
        $result = PersonData::fromResult(['name' => 'Bob', 'street' => '2 Ave']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('city', $result->errors());
        $this->assertArrayNotHasKey('address.city', $result->errors());
    }

    public function test_data_collection_item_errors_use_a_dot_path_with_the_index(): void
    {
        $result = TeamData::fromResult([
            'name' => 'Engineering',
            'members' => [
                ['name' => 'A', 'email' => 'a@example.com'],
                ['name' => 'B'],
            ],
        ]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('members.1.email', $result->errors());
    }

    public function test_data_collection_success(): void
    {
        $result = TeamData::fromResult([
            'name' => 'Engineering',
            'members' => [
                ['name' => 'A', 'email' => 'a@example.com'],
                ['name' => 'B', 'email' => 'b@example.com'],
            ],
        ]);

        $this->assertTrue($result->ok());
        $this->assertCount(2, $result->value()->members);
    }

    public function test_data_collection_item_accepts_an_already_hydrated_instance(): void
    {
        $member = UserData::from(['name' => 'A', 'email' => 'a@example.com']);
        $result = TeamData::fromResult(['name' => 'Engineering', 'members' => [$member]]);

        $this->assertTrue($result->ok());
        $this->assertSame($member, $result->value()->members->first());
    }

    public function test_hybrid_class_accumulates_errors_across_constructor_and_extra_properties(): void
    {
        $result = HybridData::fromResult(['id' => '1', 'extraId' => 'x']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('extra_label', $result->errors());
    }

    public function test_hybrid_class_success(): void
    {
        $result = HybridData::fromResult(['id' => '1', 'extraId' => 'x', 'extra_label' => 'L']);

        $this->assertTrue($result->ok());
        $this->assertSame('L', $result->value()->extraLabel);
    }

    public function test_constructor_less_class_success(): void
    {
        $result = NoConstructorData::fromResult(['required' => 'r', 'id' => 'x']);

        $this->assertTrue($result->ok());
        $this->assertSame('r', $result->value()->required);
    }

    public function test_constructor_less_class_collects_a_missing_field(): void
    {
        $result = NoConstructorData::fromResult(['id' => 'x']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('required', $result->errors());
    }

    public function test_discriminator_delegates_to_the_resolved_concrete_class(): void
    {
        $result = PaymentMethodData::fromResult(['type' => 'card', 'amount' => 100, 'last4' => '4242']);

        $this->assertTrue($result->ok());
        $this->assertInstanceOf(CardPaymentData::class, $result->value());
    }

    public function test_discriminator_reports_the_resolved_class_own_field_errors(): void
    {
        $result = PaymentMethodData::fromResult(['type' => 'card', 'amount' => 100]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('last4', $result->errors());
    }

    public function test_discriminator_unresolved_value_is_a_single_error_on_the_field(): void
    {
        $result = PaymentMethodData::fromResult(['type' => 'crypto', 'amount' => 100]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('type', $result->errors());
    }

    public function test_discriminator_missing_field_is_a_single_error(): void
    {
        $result = PaymentMethodData::fromResult(['amount' => 100]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('type', $result->errors());
    }

    public function test_reject_unknown_keys_is_a_non_blocking_accumulated_error(): void
    {
        $result = StrictUserData::fromResult(['name' => 'Alice', 'bogus' => 1]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('$unknown', $result->errors());
    }

    public function test_reject_unknown_keys_accumulates_alongside_field_errors(): void
    {
        $result = StrictUserData::fromResult(['bogus' => 1]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('$unknown', $result->errors());
        $this->assertArrayHasKey('name', $result->errors());
    }

    public function test_aliases_are_tried_in_order(): void
    {
        $result = AliasedUserData::fromResult(['uid' => 7, 'name' => 'Alice']);

        $this->assertTrue($result->ok());
        $this->assertSame(7, $result->value()->userId);
    }

    public function test_aliases_missing_all_report_the_primary_alias(): void
    {
        $result = AliasedUserData::fromResult(['name' => 'Alice']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('user_id', $result->errors());
    }

    public function test_class_level_pipe_failure_is_a_single_pipeline_error_and_skips_fields(): void
    {
        $result = ThrowingPipeData::fromResult(['name' => 'Alice']);

        $this->assertFalse($result->ok());
        $this->assertSame(['$pipeline'], array_keys($result->errors()));
    }

    public function test_class_level_pipe_failure_on_a_hybrid_class(): void
    {
        $result = ThrowingPipeHybridData::fromResult(['id' => 'x', 'extra' => 'y']);

        $this->assertFalse($result->ok());
        $this->assertSame(['$pipeline'], array_keys($result->errors()));
    }

    public function test_class_level_pipe_failure_on_a_constructor_less_class(): void
    {
        $result = ThrowingPipeNoConstructorData::fromResult(['name' => 'Alice']);

        $this->assertFalse($result->ok());
        $this->assertSame(['$pipeline'], array_keys($result->errors()));
    }

    public function test_constructor_throwing_is_collected_as_a_construct_error(): void
    {
        $result = ConstructAssertData::fromResult(['low' => 10, 'high' => 1]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('$construct', $result->errors());
    }

    public function test_constructor_not_throwing_succeeds(): void
    {
        $result = ConstructAssertData::fromResult(['low' => 1, 'high' => 10]);

        $this->assertTrue($result->ok());
    }

    public function test_nested_and_collection_fields_accept_null_when_allowed(): void
    {
        $result = CollectingEdgeCasesData::fromResult([
            'required' => ['street' => 'A', 'city' => 'B'],
            'nullable' => null,
            'requiredItems' => [],
            'nullableItems' => null,
        ]);

        $this->assertTrue($result->ok());
        $this->assertNull($result->value()->nullable);
        $this->assertNull($result->value()->nullableItems);
        $this->assertNull($result->value()->defaulted);
        $this->assertNull($result->value()->defaultedItems);
    }

    public function test_nested_and_collection_required_fields_missing_are_collected(): void
    {
        // Nullable fields with no default resolve a missing key to null — no error.
        $result = CollectingEdgeCasesData::fromResult([]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('required', $result->errors());
        $this->assertArrayHasKey('requiredItems', $result->errors());
        $this->assertArrayNotHasKey('nullable', $result->errors());
        $this->assertArrayNotHasKey('nullableItems', $result->errors());
    }

    public function test_when_loaded_field_behaves_like_a_normal_optional_field(): void
    {
        $result = WhenLoadedOrderData::fromResult(['id' => 1]);

        $this->assertTrue($result->ok());
        $this->assertNull($result->value()->client);
    }

    public function test_from_validated_result_returns_hydration_errors_when_rules_pass(): void
    {
        $result = CardPaymentData::fromValidatedResult(['type' => 'card', 'amount' => 100, 'last4' => '4242']);

        $this->assertTrue($result->ok());
    }

    public function test_from_validated_result_merges_rules_errors(): void
    {
        $result = PaymentMethodData::fromValidatedResult(['type' => 'card', 'amount' => 100, 'last4' => 'x']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('last4', $result->errors());
    }

    public function test_from_validated_result_skips_validation_when_class_has_no_rules(): void
    {
        $result = UserData::fromValidatedResult(['name' => 'Alice', 'email' => 'a@example.com']);

        $this->assertTrue($result->ok());
    }

    public function test_from_validated_result_delegates_discriminator_before_validating(): void
    {
        $result = PaymentMethodData::fromValidatedResult(['type' => 'bank', 'amount' => 100, 'iban' => 'DE123']);

        $this->assertTrue($result->ok());
        $this->assertInstanceOf(BankPaymentData::class, $result->value());
    }

    public function test_from_validated_result_unresolved_discriminator(): void
    {
        $result = PaymentMethodData::fromValidatedResult(['type' => 'crypto', 'amount' => 100]);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('type', $result->errors());
    }

    public function test_from_validated_result_normalizes_non_array_input(): void
    {
        $result = UserData::fromValidatedResult('{"name":"Zoe","email":"zoe@example.com"}');

        $this->assertTrue($result->ok());
    }

    public function test_from_validated_result_collects_an_input_error_for_invalid_non_array_input(): void
    {
        $result = UserData::fromValidatedResult('not json{{{');

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('$input', $result->errors());
    }

    public function test_collecting_hydrator_is_compiled_once_and_reused(): void
    {
        $first = UserData::fromResult(['name' => 'Alice', 'email' => 'a@example.com']);
        $second = UserData::fromResult(['name' => 'Bob', 'email' => 'b@example.com']);

        $this->assertTrue($first->ok());
        $this->assertTrue($second->ok());
        $this->assertSame('Bob', $second->value()->name);
    }

    public function test_compile_collecting_returns_the_same_cached_closure_on_a_second_call(): void
    {
        $first = HydratorCompiler::compileCollecting(UserData::class);
        $second = HydratorCompiler::compileCollecting(UserData::class);

        $this->assertSame($first, $second);
    }

    public function test_a_param_level_pipe_on_a_nested_field_runs_before_hydration(): void
    {
        $result = PipedNestedFieldData::fromResult([
            'address' => ['street' => 'A', 'city' => 'B'],
            'members' => [['name' => 'X', 'email' => 'x@example.com']],
        ]);

        $this->assertTrue($result->ok());
        $this->assertSame('A', $result->value()->address->street);
        $this->assertCount(1, $result->value()->members);
    }
}
