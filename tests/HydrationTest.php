<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProfileData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class HydrationTest extends TestCase
{
    public function test_from_array(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame('Alice', $user->name);
        $this->assertSame('alice@example.com', $user->email);
        $this->assertNull($user->phone);
    }

    public function test_from_array_with_optional_field(): void
    {
        $user = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com', 'phone' => '+380501234567']);

        $this->assertSame('+380501234567', $user->phone);
    }

    public function test_missing_required_field_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'email'/");

        UserData::from(['name' => 'Alice']);
    }

    public function test_from_stdclass(): void
    {
        $obj = (object) ['name' => 'Alice', 'email' => 'alice@example.com'];
        $user = UserData::from($obj);

        $this->assertSame('Alice', $user->name);
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_from_arrayable(): void
    {
        $collection = collect(['name' => 'Alice', 'email' => 'alice@example.com']);
        $user = UserData::from($collection);

        $this->assertSame('Alice', $user->name);
    }

    public function test_from_json_serializable(): void
    {
        $serializable = new class implements JsonSerializable
        {
            public function jsonSerialize(): array
            {
                return ['name' => 'Alice', 'email' => 'alice@example.com'];
            }
        };

        $user = UserData::from($serializable);

        $this->assertSame('Alice', $user->name);
    }

    public function test_from_existing_data_object(): void
    {
        $original = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $copy = UserData::from($original);

        $this->assertSame('Alice', $copy->name);
        $this->assertSame('alice@example.com', $copy->email);
    }

    public function test_invalid_input_type_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches('/Cannot hydrate/');

        UserData::from(42);
    }

    public function test_from_json(): void
    {
        $user = UserData::fromJson('{"name":"Alice","email":"alice@example.com"}');

        $this->assertSame('Alice', $user->name);
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_from_json_invalid_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches('/Cannot decode JSON/');

        UserData::fromJson('not valid json');
    }

    public function test_from_json_non_array_throws(): void
    {
        $this->expectException(DataHydrationException::class);

        UserData::fromJson('"just a string"');
    }

    public function test_nested_dto_hydrated_from_array(): void
    {
        $profile = ProfileData::from([
            'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
            'bio' => 'Developer',
        ]);

        $this->assertInstanceOf(UserData::class, $profile->user);
        $this->assertSame('Alice', $profile->user->name);
        $this->assertSame('Developer', $profile->bio);
    }

    public function test_nested_dto_passed_as_instance(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $profile = ProfileData::from(['user' => $user, 'bio' => 'Developer']);

        $this->assertSame('Alice', $profile->user->name);
    }

    public function test_enum_hydrated_from_string(): void
    {
        $order = OrderData::from(['id' => 1, 'status' => 'active']);

        $this->assertSame(Status::Active, $order->status);
    }

    public function test_nullable_enum_is_null_when_absent(): void
    {
        $order = OrderData::from(['id' => 1, 'status' => 'active']);

        $this->assertNull($order->previousStatus);
    }

    public function test_nullable_enum_is_null_when_explicitly_null(): void
    {
        $order = OrderData::from(['id' => 1, 'status' => 'active', 'previousStatus' => null]);

        $this->assertNull($order->previousStatus);
    }

    public function test_already_instantiated_enum_passes_through(): void
    {
        $order = OrderData::from(['id' => 1, 'status' => Status::Inactive]);

        $this->assertSame(Status::Inactive, $order->status);
    }
}
