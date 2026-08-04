<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class AliasedEnumData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_status', 'status_code')]
        public readonly Status $status,
    ) {}
}
