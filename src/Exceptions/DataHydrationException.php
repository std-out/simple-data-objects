<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Exceptions;

use RuntimeException;

final class DataHydrationException extends RuntimeException
{
    /** @param list<string> $unknownKeys */
    public function __construct(string $message, public readonly array $unknownKeys = [])
    {
        parent::__construct($message);
    }

    public static function missingField(string $class, string $field): self
    {
        return new self("Missing required field '{$field}' for {$class}.");
    }

    public static function invalidInput(string $class, string $type): self
    {
        return new self("Cannot hydrate {$class} from {$type}; expected an array, object, Traversable, or JSON string.");
    }

    public static function classNotFound(string $dataClass): self
    {
        return new self("DataCollection target class '{$dataClass}' does not exist.");
    }

    public static function invalidJson(string $class): self
    {
        return new self("Cannot decode JSON string for {$class}.");
    }

    public static function invalidEnumValue(string $enumClass, string $field, mixed $value): self
    {
        $given = is_scalar($value) ? "'".$value."'" : get_debug_type($value);

        return new self("Invalid value {$given} for field '{$field}'; expected a case of {$enumClass}.");
    }

    public static function unresolvedDiscriminator(string $class, string $field, mixed $value): self
    {
        if ($value === null) {
            return new self("Missing discriminator field '{$field}' for {$class}.");
        }

        $given = is_scalar($value) ? "'".$value."'" : get_debug_type($value);

        return new self("Unknown discriminator value {$given} in field '{$field}' for {$class}.");
    }

    public static function notADataObject(string $dataClass): self
    {
        return new self("DataCollection target class '{$dataClass}' must implement DataObject.");
    }

    /** @param list<string> $unknownKeys */
    public static function unknownKeys(string $class, array $unknownKeys): self
    {
        $list = implode(', ', array_map(static fn (string $key): string => "'{$key}'", $unknownKeys));

        return new self(
            sprintf('Unknown key%s [%s] for %s.', count($unknownKeys) === 1 ? '' : 's', $list, $class),
            $unknownKeys,
        );
    }
}
