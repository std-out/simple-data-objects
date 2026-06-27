<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Rules
{
    public function __construct(
        public readonly array $rules,
    ) {}
}
