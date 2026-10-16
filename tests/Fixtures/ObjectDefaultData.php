<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class ObjectDefaultData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly array $tags = ['a', ['b']],
        public readonly Status $status = Status::Active,
        public readonly PlainSettings $settings = new PlainSettings,
    ) {}
}
