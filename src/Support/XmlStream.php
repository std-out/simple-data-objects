<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use XMLReader;

/**
 * Only the fields a data class declares are materialized — every other
 * element is skipped without being read into memory.
 *
 * @internal
 */
final class XmlStream
{
    private const int SCALAR = 1;

    private const int LIST = 2;

    private const int NESTED = 3;

    private const int COLLECTION = 4;

    private const int STRING = 0;

    private const int INT = 1;

    private const int FLOAT = 2;

    private const int BOOL = 3;

    private const int NULLABLE = 4;

    /**
     * Per class: [attributes, text, children, initial row]. An attribute or
     * text entry is [input key, scalar type]; a child is [kind, input key,
     * scalar type or class-string].
     *
     * @var array<class-string, array{0: array<string, array>, 1: array|null, 2: array<string, array>, 3: array<string, array>}>
     */
    private static array $shapes = [];

    /**
     * @param  class-string  $class
     * @return \Generator<int, array<string, mixed>>
     */
    public static function read(string $class, string $uri, string $path): \Generator
    {
        $shape = self::$shapes[$class] ?? self::shape($class);
        $segments = explode('/', trim($path, '/'));
        $last = count($segments) - 1;
        $level = 0;

        libxml_clear_errors();

        try {
            $xml = XMLReader::fromUri($uri, null, LIBXML_NOERROR | LIBXML_NOWARNING);
        } catch (\Error $e) {
            throw DataHydrationException::unreadableXml($uri, $e->getMessage());
        }

        try {
            $more = $xml->read();

            while ($more) {
                if ($xml->nodeType === XMLReader::ELEMENT) {
                    if ($xml->name !== $segments[$level]) {
                        $more = $xml->next();

                        continue;
                    }

                    if ($level === $last) {
                        $row = $shape[3];
                        self::fill($xml, $shape, $row);

                        // A parse failure mid-element leaves a partial row behind
                        if (self::fatalError() !== null) {
                            break;
                        }

                        yield $row;

                        $more = $xml->next();

                        continue;
                    }

                    if (! $xml->isEmptyElement) {
                        $level++;
                    }
                } elseif ($xml->nodeType === XMLReader::END_ELEMENT) {
                    $level--;
                }

                $more = $xml->read();
            }

            $error = self::fatalError();

            if ($error !== null) {
                throw DataHydrationException::unreadableXml($uri, trim($error->message)." on line {$error->line}");
            }
        } finally {
            $xml->close();
        }
    }

    private static function fatalError(): ?\LibXMLError
    {
        $error = libxml_get_last_error();

        return $error !== false && $error->level === LIBXML_ERR_FATAL ? $error : null;
    }

    /**
     * Expects the reader on an element; leaves it either on that same element
     * or on its end tag, so the caller always advances with next().
     */
    private static function fill(XMLReader $xml, array $shape, array &$row): void
    {
        foreach ($shape[0] as $attribute => [$key, $type]) {
            $value = $xml->getAttribute($attribute);

            if ($value !== null) {
                $row[$key] = $type === self::STRING ? $value : self::coerce($value, $type);
            }
        }

        if ($shape[1] !== null) {
            [$key, $type] = $shape[1];
            $row[$key] = $type === self::STRING ? $xml->readString() : self::coerce($xml->readString(), $type);
        }

        $children = $shape[2];

        if ($children === [] || $xml->isEmptyElement) {
            return;
        }

        $more = $xml->read();

        while ($more) {
            if ($xml->nodeType === XMLReader::ELEMENT) {
                $node = $children[$xml->name] ?? null;

                if ($node !== null) {
                    [$kind, $key, $spec] = $node;

                    if ($kind === self::NESTED) {
                        $class = self::$shapes[$spec] ?? self::shape($spec);
                        $row[$key] = $class[3];
                        self::fill($xml, $class, $row[$key]);
                    } elseif ($kind === self::COLLECTION) {
                        $class = self::$shapes[$spec] ?? self::shape($spec);
                        $item = $class[3];
                        self::fill($xml, $class, $item);
                        $row[$key][] = $item;
                    } elseif ($kind === self::LIST) {
                        $row[$key][] = $xml->readString();
                    } else {
                        $row[$key] = $spec === self::STRING ? $xml->readString() : self::coerce($xml->readString(), $spec);
                    }
                }

                $more = $xml->next();
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT) {
                return;
            } else {
                $more = $xml->read();
            }
        }
    }

    private static function coerce(string $value, int $type): mixed
    {
        if ($type >= self::NULLABLE) {
            if ($value === '') {
                return null;
            }

            $type -= self::NULLABLE;
        }

        return match ($type) {
            self::INT => (int) $value,
            self::FLOAT => (float) $value,
            self::BOOL => $value === 'true' || $value === '1',
            default => $value,
        };
    }

    /** @param class-string $class */
    private static function shape(string $class): array
    {
        $shape = [[], null, [], []];
        self::describe($class, $shape);

        return self::$shapes[$class] = $shape;
    }

    /** @param class-string $class */
    private static function describe(string $class, array &$shape): void
    {
        $meta = MetadataRegistry::get($class);

        // The concrete class is only known once the row is read, so a
        // #[Discriminator] class collects the fields of every possible target.
        if ($meta->discriminatorField !== null) {
            $shape[2][$meta->discriminatorField] ??= [self::SCALAR, $meta->discriminatorField, self::STRING];

            $targets = $meta->discriminatorMap;

            if ($meta->discriminatorFallback !== null) {
                $targets[] = $meta->discriminatorFallback;
            }

            foreach (array_unique($targets) as $target) {
                self::describe($target, $shape);
            }

            return;
        }

        foreach ($meta->parameters as $param) {
            if ($param->flatten) {
                self::describe($param->nestedDataClass, $shape);

                continue;
            }

            [$kind, $spec] = match (true) {
                $param->caster !== null => [self::SCALAR, $param->allowsNull ? self::NULLABLE : self::STRING],
                $param->dataCollectionClass !== null => [self::COLLECTION, $param->dataCollectionClass],
                $param->nestedDataClass !== null => [self::NESTED, $param->nestedDataClass],
                $param->phpType === 'array' => [self::LIST, self::STRING],
                default => [self::SCALAR, self::scalarType($param)],
            };

            $key = (string) $param->inputNames[0];
            $source = $param->xmlSource;

            if ($source === null) {
                foreach ($param->inputNames as $name) {
                    $shape[2][(string) $name] ??= [$kind, $key, $spec];
                }
            } elseif ($source === '#text') {
                $shape[1] ??= [$key, is_int($spec) ? $spec : self::STRING];
            } elseif ($source[0] === '@') {
                $shape[0][substr($source, 1)] ??= [$key, is_int($spec) ? $spec : self::STRING];
            } else {
                $shape[2][$source] ??= [$kind, $key, $spec];
            }

            // No matching element means an empty list, not a missing field
            if (($kind === self::LIST || $kind === self::COLLECTION) && ! $param->allowsNull && ! $param->hasDefault && ! $param->isOptional) {
                $shape[3][$key] = [];
            }
        }
    }

    private static function scalarType(ParameterMeta $param): int
    {
        $type = match (true) {
            $param->enumClass !== null => (string) new \ReflectionEnum($param->enumClass)->getBackingType() === 'int' ? self::INT : self::STRING,
            $param->phpType === 'int' => self::INT,
            $param->phpType === 'float' => self::FLOAT,
            $param->phpType === 'bool' => self::BOOL,
            default => self::STRING,
        };

        return $param->allowsNull ? $type + self::NULLABLE : $type;
    }
}
