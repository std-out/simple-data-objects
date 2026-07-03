<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

/**
 * Converts non-array input into an array: Arrayable (Eloquent models,
 * collections), stdClass, JsonSerializable, any Traversable, JSON strings,
 * and plain objects (public properties). Callers check is_array() inline
 * first — the hot path (array input) never pays for this call.
 *
 * @internal
 */
final class InputNormalizer
{
    public static function normalize(string $class, mixed $data): array
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof \stdClass) {
            return (array) $data;
        }

        if ($data instanceof JsonSerializable) {
            $result = $data->jsonSerialize();

            return is_array($result) ? $result : (array) $result;
        }

        if ($data instanceof \Traversable) {
            return iterator_to_array($data);
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true, 32);

            if (! is_array($decoded)) {
                throw DataHydrationException::invalidJson($class);
            }

            return $decoded;
        }

        // Any other object: hydrate from its public properties
        if (is_object($data)) {
            return get_object_vars($data);
        }

        throw DataHydrationException::invalidInput($class, get_debug_type($data));
    }
}
