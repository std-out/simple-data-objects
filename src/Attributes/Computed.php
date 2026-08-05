<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Computed
{
    public function __construct(public readonly ?string $key = null) {}
}
