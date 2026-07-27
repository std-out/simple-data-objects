<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
class StrictHybridData extends BaseData
{
    public function __construct(
        public readonly string $id,
    ) {}

    public ?string $note = null;
}
