<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\BaseData;

#[TransformKeys(TransformKeys::CAMEL_CASE)]
class CamelCasedData extends BaseData
{
    public function __construct(
        public readonly string $first_name,
        public readonly string $last_name,
    ) {}
}
