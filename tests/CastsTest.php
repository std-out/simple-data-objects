<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;
use StdOut\SimpleDataObjects\Casts\EnumCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;

class CastsTest extends TestCase
{
    public function test_datetime_cast_hydrates_string_to_datetime(): void
    {
        $event = EventData::from(['name' => 'Conference', 'startsAt' => '2024-06-01']);

        $this->assertInstanceOf(DateTime::class, $event->startsAt);
        $this->assertSame('2024-06-01', $event->startsAt->format('Y-m-d'));
    }

    public function test_datetime_cast_serializes_to_output_format(): void
    {
        $event = EventData::from(['name' => 'Conference', 'startsAt' => '2024-06-01']);

        $this->assertSame('2024-06-01', $event->toArray()['startsAt']);
    }

    public function test_datetime_cast_passes_through_existing_datetime(): void
    {
        $dt = new DateTime('2024-06-01');
        $event = EventData::from(['name' => 'Conference', 'startsAt' => $dt]);

        $this->assertSame($dt, $event->startsAt);
    }

    public function test_datetime_cast_with_custom_input_format(): void
    {
        $cast = new DateTimeCast('Y-m-d', 'd/m/Y');
        $dt = $cast->get('15/06/2024');

        $this->assertInstanceOf(DateTime::class, $dt);
        $this->assertSame('2024-06-15', $dt->format('Y-m-d'));
    }

    public function test_datetime_cast_invalid_format_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DateTimeCast('Y-m-d', 'd/m/Y'))->get('not-a-date');
    }

    public function test_datetime_cast_null_returns_null(): void
    {
        $this->assertNull((new DateTimeCast)->get(null));
        $this->assertNull((new DateTimeCast)->set(null));
    }

    public function test_datetime_immutable_cast_hydrates_to_immutable(): void
    {
        $event = EventData::from([
            'name' => 'Conference',
            'startsAt' => '2024-06-01',
            'publishedAt' => '2024-05-01T10:00:00+00:00',
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $event->publishedAt);
    }

    public function test_datetime_immutable_cast_serializes_to_atom(): void
    {
        $event = EventData::from([
            'name' => 'Conference',
            'startsAt' => '2024-06-01',
            'publishedAt' => '2024-05-01T10:00:00+00:00',
        ]);

        $this->assertStringContainsString('2024-05-01', $event->toArray()['publishedAt']);
    }

    public function test_datetime_immutable_cast_nullable_field(): void
    {
        $event = EventData::from(['name' => 'Conference', 'startsAt' => '2024-06-01']);

        $this->assertNull($event->publishedAt);
        $this->assertNull($event->toArray()['publishedAt']);
    }

    public function test_datetime_immutable_cast_converts_mutable(): void
    {
        $cast = new DateTimeImmutableCast('Y-m-d');
        $result = $cast->get(new DateTime('2024-06-01'));

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
    }

    public function test_enum_cast_with_valid_value(): void
    {
        $payment = PaymentData::from(['amount' => 100, 'status' => 'active']);

        $this->assertSame(Status::Active, $payment->status);
    }

    public function test_enum_cast_falls_back_to_default_on_unknown_value(): void
    {
        $payment = PaymentData::from(['amount' => 100, 'status' => 'unknown']);

        $this->assertSame(Status::Inactive, $payment->status);
    }

    public function test_enum_cast_falls_back_to_default_on_null(): void
    {
        $cast = new EnumCast(Status::class, Status::Inactive);

        $this->assertSame(Status::Inactive, $cast->get(null));
    }

    public function test_enum_cast_passes_through_existing_enum(): void
    {
        $cast = new EnumCast(Status::class, Status::Inactive);

        $this->assertSame(Status::Active, $cast->get(Status::Active));
    }

    public function test_enum_cast_serializes_to_value(): void
    {
        $payment = PaymentData::from(['amount' => 100, 'status' => 'active']);

        $this->assertSame('active', $payment->toArray()['status']);
    }

    public function test_datetime_cast_with_timezone(): void
    {
        $cast = new DateTimeCast('Y-m-d H:i', null, 'Europe/Kyiv');
        $dt = $cast->get('2024-06-01 12:00:00');

        $this->assertSame('Europe/Kyiv', $dt->getTimezone()->getName());
    }
}
