<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use DOMElement;
use InvalidArgumentException;
use XMLReader;

/** @internal */
final class SampleReader
{
    public static function fromJson(string $json): SampleType
    {
        try {
            $value = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("Invalid JSON sample: {$e->getMessage()}.", 0, $e);
        }

        $type = new SampleType;
        $type->add($value);

        return $type;
    }

    /** @return array{0: SampleType, 1: string} the records and the path they were read from */
    public static function fromXml(string $uri, ?string $path = null): array
    {
        $path ??= self::recordPath($uri);
        $segments = explode('/', trim($path, '/'));
        $last = count($segments) - 1;
        $level = 0;
        $type = new SampleType;
        $xml = self::open($uri);

        $more = $xml->read();

        while ($more) {
            if ($xml->nodeType === XMLReader::ELEMENT) {
                if ($xml->name !== $segments[$level]) {
                    $more = $xml->next();

                    continue;
                }

                if ($level === $last) {
                    $element = $xml->expand();

                    if ($element instanceof DOMElement) {
                        $type->addElement($element);
                    }

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

        self::close($xml, $uri);

        if ($type->object === null) {
            throw new InvalidArgumentException("No element with attributes or child elements found at \"{$path}\" in {$uri}.");
        }

        return [$type, $path];
    }

    private static function recordPath(string $uri): string
    {
        $xml = self::open($uri);
        $root = '';
        $children = [];
        $more = $xml->read();

        while ($more && count($children) < 2) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->depth === 0) {
                $root = $xml->name;
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->depth === 1) {
                $children[] = $xml->name;
                $more = $xml->next();

                continue;
            }

            $more = $xml->read();
        }

        self::close($xml, $uri);

        return count($children) === 2 && $children[0] === $children[1] ? "{$root}/{$children[0]}" : $root;
    }

    private static function open(string $uri): XMLReader
    {
        libxml_clear_errors();

        try {
            return XMLReader::fromUri($uri, null, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } catch (\Error $e) {
            throw new InvalidArgumentException("Cannot read XML sample {$uri}: {$e->getMessage()}", 0, $e);
        }
    }

    private static function close(XMLReader $xml, string $uri): void
    {
        $xml->close();
        $error = libxml_get_last_error();

        if ($error !== false && $error->level === LIBXML_ERR_FATAL) {
            throw new InvalidArgumentException("Invalid XML sample {$uri}: ".trim($error->message)." on line {$error->line}.");
        }
    }
}
