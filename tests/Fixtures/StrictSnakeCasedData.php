<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
#[TransformKeys(TransformKeys::SNAKE_CASE)]
class StrictSnakeCasedData extends BaseData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
}
