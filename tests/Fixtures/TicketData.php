<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class TicketData extends BaseData
{
    public function __construct(
        public readonly string $title,
        public readonly Priority $priority,
        public readonly ?Priority $fallbackPriority = null,
    ) {}
}
