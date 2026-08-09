<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

/**
 * Sentinel for "key absent from input", distinct from an explicit `null`.
 * Used as a union member on a property type: `string|Optional $name`.
 */
final class Optional
{
    private static ?self $instance = null;

    private function __construct() {}

    public static function missing(): self
    {
        return self::$instance ??= new self;
    }

    public static function isMissing(mixed $value): bool
    {
        return $value instanceof self;
    }
}
