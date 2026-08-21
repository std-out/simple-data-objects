<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\TypeScriptGenerator;
use StdOut\SimpleDataObjects\Tests\Fixtures\AuthData;
use StdOut\SimpleDataObjects\Tests\Fixtures\BankPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CardPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedNameData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalFieldData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PersonData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProductData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProfileData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TaggedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TeamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TicketData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class TypeScriptGeneratorTest extends TestCase
{
    public function test_scalar_fields(): void
    {
        $ts = TypeScriptGenerator::generate([ProductData::class]);

        $this->assertStringContainsString('sku: string;', $ts);
        $this->assertStringContainsString('quantity: number;', $ts);
        $this->assertStringContainsString('price: number;', $ts);
        $this->assertStringContainsString('available: boolean;', $ts);
    }

    public function test_nullable_field_stays_required_typed_with_null_union(): void
    {
        $ts = TypeScriptGenerator::generate([UserData::class]);

        $this->assertStringContainsString('phone: string | null;', $ts);
    }

    public function test_optional_field_becomes_an_optional_key(): void
    {
        $ts = TypeScriptGenerator::generate([OptionalFieldData::class]);

        $this->assertStringContainsString('name?: string;', $ts);
    }

    public function test_optional_nullable_field_combines_optional_and_null_union(): void
    {
        $ts = TypeScriptGenerator::generate([OptionalFieldData::class]);

        $this->assertStringContainsString('bio?: string | null;', $ts);
    }

    public function test_backed_enum_becomes_a_union_of_value_literals(): void
    {
        $ts = TypeScriptGenerator::generate([CardPaymentData::class]);

        $this->assertStringContainsString("type: 'card' | 'bank';", $ts);
    }

    public function test_pure_enum_becomes_a_union_of_case_name_literals(): void
    {
        $ts = TypeScriptGenerator::generate([TicketData::class]);

        $this->assertStringContainsString("priority: 'Low' | 'High';", $ts);
    }

    public function test_nested_dto_becomes_an_interface_reference(): void
    {
        $ts = TypeScriptGenerator::generate([ProfileData::class]);

        $this->assertStringContainsString('user: UserData;', $ts);
    }

    public function test_data_collection_becomes_an_array_type(): void
    {
        $ts = TypeScriptGenerator::generate([TeamData::class]);

        $this->assertStringContainsString('members: UserData[];', $ts);
    }

    public function test_flatten_merges_fields_inline(): void
    {
        $ts = TypeScriptGenerator::generate([PersonData::class]);

        $this->assertStringContainsString('street: string;', $ts);
        $this->assertStringNotContainsString('address:', $ts);
    }

    public function test_hidden_field_is_omitted(): void
    {
        $ts = TypeScriptGenerator::generate([AuthData::class]);

        $this->assertStringContainsString('username: string;', $ts);
        $this->assertStringNotContainsString('password', $ts);
    }

    public function test_computed_field_is_typed_unknown(): void
    {
        $ts = TypeScriptGenerator::generate([ComputedNameData::class]);

        $this->assertStringContainsString('fullName: unknown;', $ts);
    }

    public function test_discriminator_becomes_a_type_alias_union(): void
    {
        $ts = TypeScriptGenerator::generate([PaymentMethodData::class]);

        $this->assertStringContainsString('export type PaymentMethodData = CardPaymentData | BankPaymentData;', $ts);
    }

    public function test_concrete_children_still_get_their_own_interface(): void
    {
        $ts = TypeScriptGenerator::generate([PaymentMethodData::class, CardPaymentData::class, BankPaymentData::class]);

        $this->assertStringContainsString('export interface CardPaymentData {', $ts);
        $this->assertStringContainsString('export interface BankPaymentData {', $ts);
    }

    public function test_generate_concatenates_multiple_classes(): void
    {
        $ts = TypeScriptGenerator::generate([UserData::class, ProductData::class]);

        $this->assertStringContainsString('export interface UserData {', $ts);
        $this->assertStringContainsString('export interface ProductData {', $ts);
    }

    public function test_discriminator_type_alias_includes_the_fallback_class(): void
    {
        $ts = TypeScriptGenerator::generate([ChannelData::class]);

        $this->assertStringContainsString('export type ChannelData = EmailChannelData | GenericChannelData;', $ts);
    }

    public function test_plain_array_field_maps_to_unknown_array(): void
    {
        $ts = TypeScriptGenerator::generate([TaggedData::class]);

        $this->assertStringContainsString('tags: unknown[];', $ts);
    }
}
