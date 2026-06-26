<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\BaseData;

class AuthData extends BaseData
{
    public function __construct(
        public readonly string $username,
        #[Hidden]
        public readonly string $password,
    ) {}
}
