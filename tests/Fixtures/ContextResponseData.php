<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

class ContextResponseData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly string $title,
        #[Hidden(except: ['admin'])]
        public readonly string $internalNote,
    ) {}
}
