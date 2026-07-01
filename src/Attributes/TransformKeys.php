<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class TransformKeys
{
    public const string SNAKE_CASE = 'snake_case';

    public const string CAMEL_CASE = 'camel_case';

    public const string STUDLY_CASE = 'studly_case';

    public const string KEBAB_CASE = 'kebab_case';

    public function __construct(public readonly string $strategy) {}
}
