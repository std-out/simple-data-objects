<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;

final class OptionalIgnoredData extends BaseData
{
    public function __construct(
        public readonly int $id,
        #[IgnoreIfNull]
        public readonly string|Optional|null $bio,
    ) {}
}
