<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class AliasedWithDefaultData extends BaseData
{
    public function __construct(
        #[MapPropertyName('nick_name', 'nickname')]
        public readonly string $nickName = 'anonymous',
    ) {}
}
