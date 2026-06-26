<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Exceptions;

use RuntimeException;

final class DataHydrationException extends RuntimeException
{
    public static function missingField(string $class, string $field): self
    {
        return new self("Missing required field '{$field}' for {$class}.");
    }

    public static function invalidInput(string $class, string $type): self
    {
        return new self("Cannot hydrate {$class} from {$type}; expected array, Arrayable, stdClass, or JsonSerializable.");
    }

    public static function classNotFound(string $dataClass): self
    {
        return new self("DataCollection target class '{$dataClass}' does not exist.");
    }

    public static function invalidJson(string $class): self
    {
        return new self("Cannot decode JSON string for {$class}.");
    }
}
