<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class TryFromTest extends TestCase
{
    public function test_returns_instance_on_valid_data(): void
    {
        $user = UserData::tryFrom(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertInstanceOf(UserData::class, $user);
        $this->assertSame('Alice', $user->name);
    }

    public function test_returns_null_on_missing_required_field(): void
    {
        $user = UserData::tryFrom(['name' => 'Alice']);

        $this->assertNull($user);
    }

    public function test_returns_null_on_empty_input(): void
    {
        $this->assertNull(UserData::tryFrom([]));
    }

    public function test_returns_null_on_invalid_type(): void
    {
        $this->assertNull(UserData::tryFrom('not-an-array'));
    }

    public function test_returns_null_on_null_input(): void
    {
        $this->assertNull(UserData::tryFrom(null));
    }

    public function test_returns_null_when_cast_fails(): void
    {
        $event = EventData::tryFrom(['name' => 'Conf', 'startsAt' => 'not-a-date']);

        $this->assertNull($event);
    }

    public function test_returns_instance_with_optional_fields_absent(): void
    {
        $user = UserData::tryFrom(['name' => 'Alice', 'email' => 'a@b.com']);

        $this->assertInstanceOf(UserData::class, $user);
        $this->assertNull($user->phone);
    }

    public function test_try_from_does_not_throw(): void
    {
        $result = UserData::tryFrom(['completely' => 'wrong', 'data' => true]);

        $this->assertNull($result);
    }
}
