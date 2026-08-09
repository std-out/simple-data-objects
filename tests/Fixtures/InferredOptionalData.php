<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;

#[InferRules]
class InferredOptionalData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly string|Optional $nickname,
        public readonly string|Optional|null $bio,
    ) {}
}
