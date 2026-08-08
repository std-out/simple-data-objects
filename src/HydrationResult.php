<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

/**
 * @template T
 */
final class HydrationResult
{
    /**
     * @param  T|null  $value
     * @param  array<string, string>  $errors
     */
    private function __construct(
        private readonly bool $ok,
        private readonly mixed $value,
        private readonly array $errors,
    ) {}

    /**
     * @template U
     *
     * @param  U  $value
     * @return self<U>
     */
    public static function success(mixed $value): self
    {
        return new self(true, $value, []);
    }

    /**
     * @param  array<string, string>  $errors
     * @return self<never>
     */
    public static function failure(array $errors): self
    {
        return new self(false, null, $errors);
    }

    public function ok(): bool
    {
        return $this->ok;
    }

    /** @return T */
    public function value(): mixed
    {
        return $this->ok
            ? $this->value
            : throw new \LogicException('Cannot read value() of a failed HydrationResult — check ok() or use errors() first.');
    }

    /** @return T|null */
    public function valueOrNull(): mixed
    {
        return $this->ok ? $this->value : null;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
