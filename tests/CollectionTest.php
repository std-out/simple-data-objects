<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\InvalidCollectionData;
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
}
