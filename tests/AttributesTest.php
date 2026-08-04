<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\KeyTransformer;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedEnumData;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedNoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedNullableData;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedWithDefaultData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CamelCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictMapPropertyNameAndInputData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictMapPropertyNameAndOutputData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmptyMapPropertyNameData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InputOnlyMappedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\KebabCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\MappedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OutputOnlyMappedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SnakeCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SplitMappedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\StudlyCasedData;

class AttributesTest extends TestCase
{
    public function test_map_property_name_remaps_input_key(): void
    {
        $data = MappedData::from(['user_name' => 'alice', 'user_id' => 42]);

        $this->assertSame('alice', $data->userName);
        $this->assertSame(42, $data->userId);
    }

    public function test_map_property_name_missing_mapped_key_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'user_name'/");

        MappedData::from(['userName' => 'alice', 'userId' => 42]);
    }

    public function test_map_property_name_to_array_uses_mapped_input_name(): void
    {
        $data = MappedData::from(['user_name' => 'alice', 'user_id' => 42]);
        $array = $data->toArray();

        $this->assertArrayHasKey('user_name', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayNotHasKey('userName', $array);
    }

    public function test_map_property_name_roundtrips(): void
    {
        $original = MappedData::from(['user_name' => 'alice', 'user_id' => 42]);
        $restored = MappedData::from($original->toArray());

        $this->assertSame('alice', $restored->userName);
        $this->assertSame(42, $restored->userId);
    }

    public function test_map_property_name_aliases_first_present_key_wins(): void
    {
        $data = AliasedUserData::from(['user_id' => 1, 'uid' => 2, 'name' => 'Alice']);

        $this->assertSame(1, $data->userId);
    }

    public function test_map_property_name_aliases_fall_back_to_a_later_alias(): void
    {
        $data = AliasedUserData::from(['uid' => 7, 'name' => 'Alice']);

        $this->assertSame(7, $data->userId);
    }

    public function test_map_property_name_aliases_missing_all_throws_naming_the_primary_alias(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'user_id'/");

        AliasedUserData::from(['name' => 'Alice']);
    }

    public function test_map_property_name_aliases_serialize_using_the_first_alias(): void
    {
        $data = AliasedUserData::from(['uid' => 7, 'name' => 'Alice']);

        $this->assertSame(['user_id' => 7, 'name' => 'Alice'], $data->toArray());
    }

    public function test_map_property_name_aliases_roundtrip(): void
    {
        $original = AliasedUserData::from(['uid' => 7, 'name' => 'Alice']);
        $restored = AliasedUserData::from($original->toArray());

        $this->assertTrue($original->equals($restored));
    }

    public function test_map_property_name_aliases_work_on_constructor_less_properties(): void
    {
        $data = AliasedNoConstructorData::from(['uid' => 5, 'name' => 'Bob']);

        $this->assertSame(5, $data->userId);
        $this->assertSame(['user_id' => 5, 'name' => 'Bob'], $data->toArray());
    }

    public function test_map_property_name_aliases_with_default_value_when_all_missing(): void
    {
        $data = AliasedWithDefaultData::from([]);

        $this->assertSame('anonymous', $data->nickName);
    }

    public function test_map_property_name_aliases_prefer_earlier_alias_over_default(): void
    {
        $data = AliasedWithDefaultData::from(['nickname' => 'Bob', 'nick_name' => 'Alice']);

        $this->assertSame('Alice', $data->nickName);
    }

    public function test_map_property_name_aliases_nullable_with_no_default_resolves_null_when_missing(): void
    {
        $data = AliasedNullableData::from([]);

        $this->assertNull($data->nickName);
    }

    public function test_map_property_name_aliases_work_with_a_caster_dependent_type(): void
    {
        $data = AliasedEnumData::from(['status_code' => 'active']);

        $this->assertSame(Status::Active, $data->status);
    }

    public function test_map_property_name_with_no_arguments_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/requires at least one input name/');

        EmptyMapPropertyNameData::from(['userId' => 'x']);
    }

    public function test_map_property_name_cannot_combine_with_map_input_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/#\[MapPropertyName\] cannot be combined with #\[MapInputName\]\/#\[MapOutputName\]/');

        ConflictMapPropertyNameAndInputData::from(['user_id' => 1]);
    }

    public function test_map_property_name_cannot_combine_with_map_output_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/#\[MapPropertyName\] cannot be combined with #\[MapInputName\]\/#\[MapOutputName\]/');

        ConflictMapPropertyNameAndOutputData::from(['user_id' => 1]);
    }

    public function test_map_input_name_and_map_output_name_use_independent_keys(): void
    {
        $data = SplitMappedData::from(['legacy_id' => 9, 'name' => 'Alice']);

        $this->assertSame(9, $data->userId);
        $this->assertSame(['id' => 9, 'name' => 'Alice'], $data->toArray());
    }

    public function test_map_input_name_and_map_output_name_roundtrip_via_the_output_key(): void
    {
        $original = SplitMappedData::from(['legacy_id' => 9, 'name' => 'Alice']);
        $restored = SplitMappedData::from($original->toArray());

        $this->assertTrue($original->equals($restored));
    }

    public function test_map_output_name_alone_still_accepts_the_default_input_key(): void
    {
        $data = OutputOnlyMappedData::from(['userId' => 3]);

        $this->assertSame(3, $data->userId);
        $this->assertSame(['id' => 3], $data->toArray());
    }

    public function test_map_output_name_alone_also_accepts_the_output_key_on_input(): void
    {
        $data = OutputOnlyMappedData::from(['id' => 3]);

        $this->assertSame(3, $data->userId);
    }

    public function test_map_input_name_alone_prefers_its_aliases_over_the_default_key(): void
    {
        // 'userId' (the output key) is still an accepted fallback, but loses to the alias.
        $data = InputOnlyMappedData::from(['old_id' => 1, 'userId' => 2]);

        $this->assertSame(1, $data->userId);
    }

    public function test_map_input_name_alone_still_accepts_the_default_key_as_a_fallback(): void
    {
        $data = InputOnlyMappedData::from(['userId' => 3]);

        $this->assertSame(3, $data->userId);
    }

    public function test_map_input_name_alone_still_serializes_under_the_default_name(): void
    {
        $data = InputOnlyMappedData::from(['old_id' => 3]);

        $this->assertSame(['userId' => 3], $data->toArray());
    }

    public function test_map_input_name_alone_roundtrips_via_the_default_output_key(): void
    {
        $original = InputOnlyMappedData::from(['old_id' => 3]);
        $restored = InputOnlyMappedData::from($original->toArray());

        $this->assertTrue($original->equals($restored));
    }

    public function test_transform_keys_snake_case_reads_snake_input(): void
    {
        $data = SnakeCasedData::from(['first_name' => 'Alice', 'last_name' => 'Smith']);

        $this->assertSame('Alice', $data->firstName);
        $this->assertSame('Smith', $data->lastName);
    }

    public function test_transform_keys_snake_case_missing_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'first_name'/");

        SnakeCasedData::from(['firstName' => 'Alice', 'lastName' => 'Smith']);
    }

    public function test_transform_keys_camel_case_reads_camel_input(): void
    {
        $data = CamelCasedData::from(['firstName' => 'Alice', 'lastName' => 'Smith']);

        $this->assertSame('Alice', $data->first_name);
        $this->assertSame('Smith', $data->last_name);
    }

    public function test_transform_keys_camel_case_missing_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'firstName'/");

        CamelCasedData::from(['first_name' => 'Alice', 'last_name' => 'Smith']);
    }

    public function test_transform_keys_studly_case_reads_studly_input(): void
    {
        $data = StudlyCasedData::from(['FirstName' => 'Alice', 'LastName' => 'Smith']);

        $this->assertSame('Alice', $data->firstName);
        $this->assertSame('Smith', $data->lastName);
    }

    public function test_transform_keys_studly_case_roundtrips(): void
    {
        $original = StudlyCasedData::from(['FirstName' => 'Alice', 'LastName' => 'Smith']);
        $array = $original->toArray();

        $this->assertArrayHasKey('FirstName', $array);
        $this->assertArrayHasKey('LastName', $array);

        $restored = StudlyCasedData::from($array);
        $this->assertSame('Alice', $restored->firstName);
    }

    public function test_transform_keys_kebab_case_reads_kebab_input(): void
    {
        $data = KebabCasedData::from(['first-name' => 'Alice', 'last-name' => 'Smith']);

        $this->assertSame('Alice', $data->firstName);
        $this->assertSame('Smith', $data->lastName);
    }

    public function test_transform_keys_kebab_case_roundtrips(): void
    {
        $original = KebabCasedData::from(['first-name' => 'Alice', 'last-name' => 'Smith']);
        $array = $original->toArray();

        $this->assertArrayHasKey('first-name', $array);

        $restored = KebabCasedData::from($array);
        $this->assertSame('Alice', $restored->firstName);
    }

    public function test_key_transformer_unknown_strategy_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown key transform strategy/');

        KeyTransformer::apply('firstName', 'nonsense_strategy');
    }
}
