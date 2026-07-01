<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\KeyTransformer;
use StdOut\SimpleDataObjects\Tests\Fixtures\CamelCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\KebabCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\MappedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SnakeCasedData;
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
