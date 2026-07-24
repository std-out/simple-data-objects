<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorData;
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

    public function test_with_unknown_property_throws(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown property \[nickname\]/');

        $user->with(nickname: 'Al');
    }

    public function test_with_multiple_unknown_properties_throws(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown properties \[nickname, age\]/');

        $user->with(nickname: 'Al', age: 30);
    }

    public function test_with_returns_new_instance_for_class_without_constructor(): void
    {
        $original = NoConstructorData::from(['required' => 'r', 'id' => 'abc']);
        $updated = $original->with(required: 'r2');

        $this->assertNotSame($original, $updated);
        $this->assertSame('r2', $updated->required);
    }

    public function test_with_does_not_mutate_original_for_class_without_constructor(): void
    {
        $original = NoConstructorData::from(['required' => 'r', 'id' => 'abc']);
        $original->with(required: 'r2');

        $this->assertSame('r', $original->required);
    }

    public function test_with_preserves_unchanged_fields_for_class_without_constructor(): void
    {
        $original = NoConstructorData::from(['required' => 'r', 'id' => 'abc', 'priority' => 9]);
        $updated = $original->with(required: 'r2');

        $this->assertSame('abc', $updated->id);
        $this->assertSame(9, $updated->priority);
    }

    public function test_with_can_override_readonly_property_for_class_without_constructor(): void
    {
        $original = NoConstructorData::from(['required' => 'r', 'id' => 'abc']);
        $updated = $original->with(id: 'xyz');

        $this->assertSame('xyz', $updated->id);
        $this->assertSame('abc', $original->id);
    }

    public function test_with_unknown_property_throws_for_class_without_constructor(): void
    {
        $original = NoConstructorData::from(['required' => 'r', 'id' => 'abc']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown property \[nickname\]/');

        $original->with(nickname: 'Al');
    }

    private function hybrid(): HybridData
    {
        return HybridData::from(['id' => '1', 'extraId' => 'e1', 'extra_label' => 'Label']);
    }

    public function test_with_overrides_constructor_field_for_hybrid_class(): void
    {
        $updated = $this->hybrid()->with(status: 'active');

        $this->assertSame('active', $updated->status);
        $this->assertSame('e1', $updated->extraId);
    }

    public function test_with_overrides_extra_field_for_hybrid_class(): void
    {
        $updated = $this->hybrid()->with(note: 'hello');

        $this->assertSame('hello', $updated->note);
        $this->assertSame('1', $updated->id);
    }

    public function test_with_overrides_both_constructor_and_extra_fields_for_hybrid_class(): void
    {
        $updated = $this->hybrid()->with(status: 'active', extraId: 'e2');

        $this->assertSame('active', $updated->status);
        $this->assertSame('e2', $updated->extraId);
        $this->assertSame('Label', $updated->extraLabel);
    }

    public function test_with_can_override_readonly_extra_property_for_hybrid_class(): void
    {
        $original = $this->hybrid();
        $updated = $original->with(extraId: 'e2');

        $this->assertSame('e2', $updated->extraId);
        $this->assertSame('e1', $original->extraId);
    }

    public function test_with_preserves_unchanged_fields_for_hybrid_class(): void
    {
        $original = $this->hybrid();
        $updated = $original->with(note: 'hello');

        $this->assertSame($original->id, $updated->id);
        $this->assertSame($original->status, $updated->status);
        $this->assertSame($original->extraId, $updated->extraId);
        $this->assertSame($original->extraLabel, $updated->extraLabel);
    }

    public function test_with_unknown_property_throws_for_hybrid_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown property \[nickname\]/');

        $this->hybrid()->with(nickname: 'Al');
    }
}
