<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProfileData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TeamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class WithTest extends TestCase
{
    public function test_with_returns_new_instance(): void
    {
        $original = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $updated = $original->with(name: 'Bob');

        $this->assertNotSame($original, $updated);
    }

    public function test_with_does_not_mutate_original(): void
    {
        $original = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $original->with(name: 'Bob');

        $this->assertSame('Alice', $original->name);
    }

    public function test_with_changes_specified_field(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $updated = $user->with(email: 'bob@example.com');

        $this->assertSame('bob@example.com', $updated->email);
    }

    public function test_with_preserves_unchanged_fields(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $updated = $user->with(email: 'new@example.com');

        $this->assertSame('Alice', $updated->name);
        $this->assertNull($updated->phone);
    }

    public function test_with_multiple_fields(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $updated = $user->with(name: 'Bob', email: 'bob@example.com', phone: '+380991234567');

        $this->assertSame('Bob', $updated->name);
        $this->assertSame('bob@example.com', $updated->email);
        $this->assertSame('+380991234567', $updated->phone);
    }

    public function test_with_applies_cast_to_overridden_value(): void
    {
        $event = EventData::from(['name' => 'Conf', 'startsAt' => '2024-01-01']);
        $updated = $event->with(startsAt: '2025-06-15');

        $this->assertInstanceOf(DateTime::class, $updated->startsAt);
        $this->assertSame('2025-06-15', $updated->startsAt->format('Y-m-d'));
    }

    public function test_with_passes_through_already_cast_value(): void
    {
        $dt = new DateTime('2025-06-15');
        $event = EventData::from(['name' => 'Conf', 'startsAt' => '2024-01-01']);
        $updated = $event->with(startsAt: $dt);

        $this->assertSame($dt, $updated->startsAt);
    }

    public function test_with_nested_dto(): void
    {
        $profile = ProfileData::from([
            'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
            'bio' => 'Developer',
        ]);

        $newUser = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com']);
        $updated = $profile->with(bio: 'Designer');

        $this->assertSame('Developer', $profile->bio);
        $this->assertSame('Designer', $updated->bio);
        $this->assertSame('Alice', $updated->user->name);
    }

    public function test_with_chaining(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $result = $user
            ->with(name: 'Bob')
            ->with(email: 'bob@example.com')
            ->with(phone: '123');

        $this->assertSame('Bob', $result->name);
        $this->assertSame('bob@example.com', $result->email);
        $this->assertSame('123', $result->phone);
    }

    public function test_with_collection_field(): void
    {
        $team = TeamData::from([
            'name' => 'Dev',
            'members' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
            ],
        ]);

        $updated = $team->with(name: 'Design');

        $this->assertSame('Design', $updated->name);
        $this->assertCount(1, $updated->members);
    }
}
