<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;
use StdOut\SimpleDataObjects\Contracts\CastsValue;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Cast
{
    public function __construct(
        public readonly CastsValue $caster,
    ) {}
}
