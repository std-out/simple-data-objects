<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Discriminator
{
    /**
     * @param  array<int|string, class-string>  $map
     * @param  class-string|null  $fallback
     */
    public function __construct(
        public readonly string $field,
        public readonly array $map,
        public readonly ?string $fallback = null,
    ) {}
}
