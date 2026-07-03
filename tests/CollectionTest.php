<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\InvalidCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NotDataObjectCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TeamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class CollectionTest extends TestCase
{
    public function test_collection_returns_typed_collection(): void
    {
        $collection = UserData::collection([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $this->assertInstanceOf(TypedDataCollection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function test_collection_items_are_hydrated(): void
    {
        $collection = UserData::collection([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);

        $this->assertInstanceOf(UserData::class, $collection->first());
        $this->assertSame('Alice', $collection->first()->name);
    }

    public function test_collection_passes_through_existing_instances(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $collection = UserData::collection([$user]);

        $this->assertSame($user, $collection->first());
    }

    public function test_collection_from_empty_iterable(): void
    {
        $collection = UserData::collection([]);

        $this->assertInstanceOf(TypedDataCollection::class, $collection);
        $this->assertCount(0, $collection);
    }

    public function test_data_collection_attribute_hydrates_nested_collection(): void
    {
        $team = TeamData::from([
            'name' => 'Dev',
            'members' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com'],
            ],
        ]);

        $this->assertInstanceOf(TypedDataCollection::class, $team->members);
        $this->assertCount(2, $team->members);
        $this->assertInstanceOf(UserData::class, $team->members->first());
    }

    public function test_data_collection_attribute_class_not_found_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches('/does not exist/');

        InvalidCollectionData::from(['items' => []]);
    }

    public function test_data_collection_attribute_requires_data_object_target(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches('/must implement DataObject/');

        NotDataObjectCollectionData::from(['items' => []]);
    }

    public function test_typed_data_collection_is_iterable(): void
    {
        $collection = UserData::collection([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $names = [];

        foreach ($collection as $user) {
            $names[] = $user->name;
        }

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function test_of_factory_creates_collection_from_arrays(): void
    {
        $collection = TypedDataCollection::of(UserData::class, [
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $this->assertInstanceOf(TypedDataCollection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertInstanceOf(UserData::class, $collection->first());
    }

    public function test_of_factory_passes_through_existing_instances(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $collection = TypedDataCollection::of(UserData::class, [$user]);

        $this->assertSame($user, $collection->first());
    }

    public function test_of_factory_with_empty_items(): void
    {
        $collection = TypedDataCollection::of(UserData::class);

        $this->assertInstanceOf(TypedDataCollection::class, $collection);
        $this->assertCount(0, $collection);
    }

    public function test_of_factory_rejects_non_data_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must extend/');

        TypedDataCollection::of(\stdClass::class, [['x' => 1]]);
    }

    public function test_null_value_for_non_nullable_collection_returns_empty_collection(): void
    {
        $team = TeamData::from(['name' => 'Dev', 'members' => null]);

        $this->assertInstanceOf(TypedDataCollection::class, $team->members);
        $this->assertCount(0, $team->members);
    }

    public function test_typed_data_collection_last(): void
    {
        $collection = UserData::collection([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $last = $collection->last();

        $this->assertInstanceOf(UserData::class, $last);
        $this->assertSame('Bob', $last->name);
    }

    public function test_lazy_collection_returns_lazy_collection(): void
    {
        $collection = UserData::lazyCollection([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function test_lazy_collection_items_are_hydrated(): void
    {
        $collection = UserData::lazyCollection([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);

        $this->assertInstanceOf(UserData::class, $collection->first());
        $this->assertSame('Alice', $collection->first()->name);
    }

    public function test_lazy_collection_passes_through_existing_instances(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $collection = UserData::lazyCollection([$user]);

        $this->assertSame($user, $collection->first());
    }

    public function test_lazy_collection_does_not_hydrate_until_consumed(): void
    {
        $hydrated = 0;

        $source = (static function () use (&$hydrated): \Generator {
            foreach (range(1, 3) as $i) {
                $hydrated++;
                yield ['name' => "User $i", 'email' => "user{$i}@example.com"];
            }
        })();

        $collection = UserData::lazyCollection($source);

        $this->assertSame(0, $hydrated);

        $first = $collection->first();

        $this->assertSame('User 1', $first->name);
        $this->assertSame(1, $hydrated);
    }

    public function test_lazy_collection_stops_hydrating_once_enough_items_are_taken(): void
    {
        $hydrated = 0;

        $source = (static function () use (&$hydrated): \Generator {
            foreach (range(1, 1000) as $i) {
                $hydrated++;
                yield ['name' => "User $i", 'email' => "user{$i}@example.com"];
            }
        })();

        $names = UserData::lazyCollection($source)
            ->take(3)
            ->map(fn (UserData $user): string => $user->name)
            ->all();

        $this->assertSame(['User 1', 'User 2', 'User 3'], $names);
        $this->assertSame(3, $hydrated);
    }
}
