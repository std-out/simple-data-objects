<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\SchemaGenerator;
use StdOut\SimpleDataObjects\Tests\Fixtures\AuthData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CardPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedNameData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\FilterData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredEnumData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredTreeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OptionalFieldData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithMoneyData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithUuidData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PersonData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProductData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProfileData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SchemaRulesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SecretData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TeamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TicketData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UntypedFieldData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ValidatedUserData;

class SchemaGeneratorTest extends TestCase
{
    public function test_scalar_and_cast_types(): void
    {
        $schema = ProductData::jsonSchema();

        $this->assertSame(['type' => 'string'], $schema['properties']['sku']);
        $this->assertSame(['type' => 'integer'], $schema['properties']['quantity']);
        $this->assertSame(['type' => 'number'], $schema['properties']['price']);
        $this->assertSame(['type' => 'boolean'], $schema['properties']['available']);
        $this->assertSame([], $schema['properties']['meta']);
    }

    public function test_nullable_field_folds_type_into_an_array(): void
    {
        $schema = UserData::jsonSchema();

        $this->assertSame(['type' => ['string', 'null']], $schema['properties']['phone']);
    }

    public function test_required_excludes_nullable_and_defaulted_fields(): void
    {
        $schema = UserData::jsonSchema();

        $this->assertSame(['name', 'email'], $schema['required']);
    }

    public function test_optional_field_is_excluded_from_required_and_not_nullable(): void
    {
        $schema = OptionalFieldData::jsonSchema();

        $this->assertSame(['id'], $schema['required']);
        $this->assertSame(['type' => 'string'], $schema['properties']['name']);
    }

    public function test_backed_enum_maps_to_value_literals(): void
    {
        $schema = CardPaymentData::jsonSchema();

        $this->assertSame('string', $schema['properties']['type']['type']);
        $this->assertSame(['card', 'bank'], $schema['properties']['type']['enum']);
    }

    public function test_pure_enum_maps_to_case_name_literals(): void
    {
        $schema = TicketData::jsonSchema();

        $this->assertSame(['Low', 'High'], $schema['properties']['priority']['enum']);
    }

    public function test_nested_dto_becomes_a_ref_with_a_def(): void
    {
        $schema = ProfileData::jsonSchema();

        $this->assertSame(['$ref' => '#/$defs/UserData'], $schema['properties']['user']);
        $this->assertArrayHasKey('UserData', $schema['$defs']);
        $this->assertSame('object', $schema['$defs']['UserData']['type']);
    }

    public function test_data_collection_becomes_an_array_of_ref(): void
    {
        $schema = TeamData::jsonSchema();

        $this->assertSame('array', $schema['properties']['members']['type']);
        $this->assertSame(['$ref' => '#/$defs/UserData'], $schema['properties']['members']['items']);
    }

    public function test_flatten_merges_properties_inline_without_a_ref(): void
    {
        $schema = PersonData::jsonSchema();

        $this->assertArrayHasKey('street', $schema['properties']);
        $this->assertArrayHasKey('city', $schema['properties']);
        $this->assertArrayNotHasKey('address', $schema['properties']);
        $this->assertContains('street', $schema['required']);
    }

    public function test_hidden_field_is_omitted(): void
    {
        $schema = AuthData::jsonSchema();

        $this->assertArrayHasKey('username', $schema['properties']);
        $this->assertArrayNotHasKey('password', $schema['properties']);
    }

    public function test_cyclic_self_reference_does_not_infinite_loop(): void
    {
        $schema = InferredTreeData::jsonSchema();

        $this->assertSame(['$ref' => '#/$defs/InferredTreeData'], $schema['properties']['parent']);
        $this->assertArrayHasKey('InferredTreeData', $schema['$defs']);
    }

    public function test_datetime_cast_default_atom_format_maps_to_date_time(): void
    {
        $schema = EventData::jsonSchema();

        $this->assertSame(['type' => ['string', 'null'], 'format' => 'date-time'], $schema['properties']['publishedAt']);
    }

    public function test_datetime_cast_custom_format_has_no_format_keyword(): void
    {
        $schema = EventData::jsonSchema();

        $this->assertSame(['type' => 'string'], $schema['properties']['startsAt']);
    }

    public function test_encrypted_cast_maps_to_string(): void
    {
        $schema = SecretData::jsonSchema();

        $this->assertSame(['type' => 'string'], $schema['properties']['token']);
    }

    public function test_uuid_cast_maps_to_string_with_uuid_format(): void
    {
        $schema = OrderWithUuidData::jsonSchema();

        $this->assertSame(['type' => 'string', 'format' => 'uuid'], $schema['properties']['id']);
    }

    public function test_money_cast_maps_to_integer(): void
    {
        $schema = OrderWithMoneyData::jsonSchema();

        $this->assertSame('integer', $schema['properties']['price']['type']);
    }

    public function test_comma_separated_cast_maps_to_string(): void
    {
        $schema = FilterData::jsonSchema();

        $this->assertSame(['type' => 'string'], $schema['properties']['tags']);
    }

    public function test_rules_email_and_max_length_are_mapped(): void
    {
        $schema = ValidatedUserData::jsonSchema();

        $this->assertSame('email', $schema['properties']['email']['format']);
        $this->assertSame(100, $schema['properties']['name']['maxLength']);
    }

    public function test_rules_in_maps_to_enum(): void
    {
        $schema = SchemaRulesData::jsonSchema();

        $this->assertSame(['draft', 'published', 'archived'], $schema['properties']['status']['enum']);
    }

    public function test_rules_url_maps_to_format_uri(): void
    {
        $schema = SchemaRulesData::jsonSchema();

        $this->assertSame('uri', $schema['properties']['website']['format']);
    }

    public function test_rules_max_min_on_array_type_map_to_items_bounds(): void
    {
        $schema = SchemaRulesData::jsonSchema();

        $this->assertSame(5, $schema['properties']['tags']['maxItems']);
        $this->assertSame(1, $schema['properties']['tags']['minItems']);
    }

    public function test_unmapped_rules_are_silently_ignored(): void
    {
        $schema = ValidatedUserData::jsonSchema();

        $this->assertSame(['type' => ['string', 'null'], 'minLength' => 6, 'maxLength' => 20], $schema['properties']['username']);
    }

    public function test_discriminator_maps_to_one_of_with_refs(): void
    {
        $schema = PaymentMethodData::jsonSchema();

        $this->assertSame(
            [['$ref' => '#/$defs/CardPaymentData'], ['$ref' => '#/$defs/BankPaymentData']],
            $schema['oneOf'],
        );
    }

    public function test_discriminator_includes_the_fallback_class(): void
    {
        $schema = ChannelData::jsonSchema();

        $this->assertSame(
            [['$ref' => '#/$defs/EmailChannelData'], ['$ref' => '#/$defs/GenericChannelData']],
            $schema['oneOf'],
        );
    }

    public function test_generate_omits_defs_key_when_there_is_nothing_nested(): void
    {
        $schema = UserData::jsonSchema();

        $this->assertArrayNotHasKey('$defs', $schema);
    }

    public function test_base_data_json_schema_delegates_to_schema_generator(): void
    {
        $this->assertSame(SchemaGenerator::generate(UserData::class), UserData::jsonSchema());
    }

    public function test_computed_field_is_included_with_an_open_schema(): void
    {
        $schema = ComputedNameData::jsonSchema();

        $this->assertSame([], $schema['properties']['fullName']);
    }

    public function test_plain_int_and_float_and_bool_fields_map_to_scalar_types(): void
    {
        $schema = SchemaRulesData::jsonSchema();

        $this->assertSame('number', $schema['properties']['score']['type']);
        $this->assertSame('boolean', $schema['properties']['flag']['type']);
    }

    public function test_rules_max_min_on_a_numeric_type_map_to_maximum_minimum(): void
    {
        $schema = SchemaRulesData::jsonSchema();

        $this->assertSame(150.0, $schema['properties']['age']['maximum']);
        $this->assertSame(0.0, $schema['properties']['age']['minimum']);
    }

    public function test_bound_rule_on_an_unsupported_type_is_silently_skipped(): void
    {
        $schema = SchemaRulesData::jsonSchema();

        $this->assertArrayNotHasKey('maximum', $schema['properties']['flag']);
        $this->assertArrayNotHasKey('maxLength', $schema['properties']['flag']);
    }

    public function test_non_string_rule_entries_are_silently_skipped(): void
    {
        $schema = InferredEnumData::jsonSchema();

        $this->assertSame('string', $schema['properties']['status']['type']);
        $this->assertArrayHasKey('enum', $schema['properties']['status']);
    }

    public function test_completely_untyped_field_gets_an_open_schema(): void
    {
        $schema = UntypedFieldData::jsonSchema();

        $this->assertSame([], $schema['properties']['anything']);
        $this->assertSame(['name'], $schema['required']);
    }
}
