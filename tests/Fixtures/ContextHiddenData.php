<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\BaseData;

class ContextHiddenData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[Hidden(except: ['admin'])]
        public readonly string $internalNote,
        #[Hidden]
        public readonly string $secret,
    ) {}
}
