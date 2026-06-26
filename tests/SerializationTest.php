<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\AuthData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NullableIgnoredData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProfileData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TaggedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TeamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class SerializationTest extends TestCase
{
    public function test_to_array_basic(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame(
            ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null],
            $user->toArray(),
        );
    }

    public function test_hidden_field_excluded_from_array(): void
    {
        $auth = AuthData::from(['username' => 'alice', 'password' => 's3cr3t']);
        $array = $auth->toArray();

        $this->assertArrayHasKey('username', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_ignore_if_null_omits_null_field(): void
    {
        $array = NullableIgnoredData::from(['name' => 'Alice'])->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('optional', $array);
    }

    public function test_ignore_if_null_includes_non_null_field(): void
    {
        $array = NullableIgnoredData::from(['name' => 'Alice', 'optional' => 'present'])->toArray();

        $this->assertSame('present', $array['optional']);
    }

    public function test_nested_dto_serialized_as_array(): void
    {
        $array = ProfileData::from([
            'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
            'bio' => 'Developer',
        ])->toArray();

        $this->assertIsArray($array['user']);
        $this->assertSame('Alice', $array['user']['name']);
    }

    public function test_backed_enum_serialized_to_value(): void
    {
        $order = OrderData::from(['id' => 1, 'status' => 'active']);

        $this->assertSame('active', $order->toArray()['status']);
    }

    public function test_collection_field_serialized_as_array(): void
    {
        $array = TeamData::from([
            'name' => 'Dev',
            'members' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com'],
            ],
        ])->toArray();

        $this->assertIsArray($array['members']);
        $this->assertCount(2, $array['members']);
        $this->assertSame('Alice', $array['members'][0]['name']);
    }

    public function test_plain_array_field_passes_through(): void
    {
        $data = TaggedData::from(['tags' => ['php', 'dto']]);

        $this->assertSame(['tags' => ['php', 'dto']], $data->toArray());
    }

    public function test_to_json(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $json = $user->toJson();

        $this->assertJson($json);
        $this->assertSame('Alice', json_decode($json, true)['name']);
    }

    public function test_to_string_returns_json(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertJson((string) $user);
    }

    public function test_json_serialize_returns_array(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame($user->toArray(), $user->jsonSerialize());
    }

    public function test_only_returns_subset(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame(['name' => 'Alice'], $user->only('name'));
    }

    public function test_except_excludes_keys(): void
    {
        $result = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com'])->except('phone');

        $this->assertArrayNotHasKey('phone', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
    }
}
