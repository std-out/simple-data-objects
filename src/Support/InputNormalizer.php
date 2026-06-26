<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

final class InputNormalizer
{
    public static function normalize(string $class, mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

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

        throw DataHydrationException::invalidInput($class, get_debug_type($data));
    }
}
