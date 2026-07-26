<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\FakeLivewireWireable;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\WireableEventData;

class WireableDataTest extends TestCase
{
    public function test_class_satisfies_the_wireable_contract_shape(): void
    {
        $event = WireableEventData::from([
            'name' => 'Conf',
            'startsAt' => '2024-06-01',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(FakeLivewireWireable::class, $event);
    }

    public function test_to_livewire_matches_to_array(): void
    {
        $event = WireableEventData::from([
            'name' => 'Conf',
            'startsAt' => '2024-06-01',
            'status' => 'active',
        ]);

        $this->assertSame($event->toArray(), $event->toLivewire());
    }

    public function test_from_livewire_hydrates_like_from(): void
    {
        $payload = ['name' => 'Conf', 'startsAt' => '2024-06-01', 'status' => 'active'];

        $event = WireableEventData::fromLivewire($payload);

        $this->assertSame('Conf', $event->name);
        $this->assertSame('2024-06-01', $event->startsAt->format('Y-m-d'));
        $this->assertSame(Status::Active, $event->status);
    }

    public function test_round_trip_through_livewire_methods_preserves_casts(): void
    {
        $original = WireableEventData::from([
            'name' => 'Conf',
            'startsAt' => '2024-06-01',
            'status' => 'inactive',
        ]);

        $restored = WireableEventData::fromLivewire($original->toLivewire());

        $this->assertTrue($original->equals($restored));
        $this->assertSame(Status::Inactive, $restored->status);
    }
}
