<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use DateTime;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Concerns\WireableData;

class WireableEventData extends BaseData implements FakeLivewireWireable
{
    use WireableData;

    public function __construct(
        public readonly string $name,
        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime $startsAt,
        public readonly Status $status,
    ) {}
}
