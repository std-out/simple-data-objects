<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Rules
{
    /** @param  array<mixed>  $rules */
    public function __construct(
        public readonly array $rules,
        public readonly bool $merge = false,
    ) {}
}
