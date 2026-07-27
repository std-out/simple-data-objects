<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
class StrictUserData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[MapPropertyName('user_email')]
        public readonly ?string $email = null,
    ) {}
}
