<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\BaseData;

#[TransformKeys(TransformKeys::SNAKE_CASE)]
class SnakeCasedData extends BaseData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
}
