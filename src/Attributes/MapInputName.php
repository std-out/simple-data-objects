<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class MapInputName
{
    /** @var list<string|int> */
    public readonly array $inputs;

    public function __construct(string|int ...$inputs)
    {
        $this->inputs = $inputs;
    }
}
