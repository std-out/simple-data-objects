<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

final class CommaSeparatedCast implements CastsValue, ProvidesJsonSchema
{
    public function __construct(
        private readonly string $separator = ',',
        private readonly bool $trim = true,
        private readonly ?int $maxItems = null,
    ) {
        if ($maxItems !== null && $maxItems < 1) {
            throw new InvalidArgumentException('CommaSeparatedCast $maxItems must be at least 1.');
        }
    }

    public static function __set_state(array $state): self
    {
        return new self($state['separator'], $state['trim'], $state['maxItems'] ?? null);
    }

    public function get(#[\SensitiveParameter] mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $this->maxItems === null || count($value) <= $this->maxItems ? $value : $this->tooMany();
        }

        $str = (string) $value;

        if ($str === '') {
            return [];
        }

        if ($this->maxItems === null) {
            $parts = explode($this->separator, $str);
        } elseif (count($parts = explode($this->separator, $str, $this->maxItems + 1)) > $this->maxItems) {
            $this->tooMany();
        }

        return $this->trim ? array_map('trim', $parts) : $parts;
    }

    public function set(#[\SensitiveParameter] mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return implode($this->separator, $value);
    }

    private function tooMany(): never
    {
        throw new InvalidArgumentException("CommaSeparatedCast accepts at most {$this->maxItems} items.");
    }

    public function jsonSchema(): array
    {
        return ['type' => 'string'];
    }
}
