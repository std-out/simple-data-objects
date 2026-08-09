<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Hidden
{
    /** @param list<string> $except */
    public function __construct(public readonly array $except = []) {}
}
