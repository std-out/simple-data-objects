<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * A minimal immutable money value, storing the amount in minor units
 * (e.g. cents) to avoid float rounding error. Assumes a fixed number of
 * decimal places per operation (2 by default) rather than an ISO 4217
 * currency-decimals table — for currencies that don't use 2 decimal
 * places (JPY, BHD, ...), pass `$decimals` explicitly to `toDecimal()`.
 */
final class Money implements Stringable
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}

    public function toDecimal(int $decimals = 2): string
    {
        return number_format($this->amount / (10 ** $decimals), $decimals, '.', '');
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function __toString(): string
    {
        return "{$this->toDecimal()} {$this->currency}";
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot combine {$this->currency} with {$other->currency}.",
            );
        }
    }
}
