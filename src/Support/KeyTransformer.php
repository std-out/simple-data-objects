<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Attributes\TransformKeys;

final class KeyTransformer
{
    public static function apply(string $phpName, string $strategy): string
    {
        return match ($strategy) {
            TransformKeys::SNAKE_CASE => strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $phpName)),
            TransformKeys::CAMEL_CASE => lcfirst(str_replace('_', '', ucwords($phpName, '_'))),
            default => throw new \InvalidArgumentException("Unknown key transform strategy: \"{$strategy}\"."),
        };
    }
}
