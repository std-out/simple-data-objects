<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\CamelCasedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\MappedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SnakeCasedData;

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

    public function test_map_property_name_to_array_uses_php_name(): void
    {
        $data = MappedData::from(['user_name' => 'alice', 'user_id' => 42]);
        $array = $data->toArray();

        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayNotHasKey('user_name', $array);
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
}
