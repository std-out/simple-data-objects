<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;

class EventData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime $startsAt,
        #[Cast(new DateTimeImmutableCast(DateTimeInterface::ATOM))]
        public readonly ?DateTimeImmutable $publishedAt = null,
    ) {}
}
