<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class WrapIn
{
    public function __construct(public readonly string $key) {}
}
