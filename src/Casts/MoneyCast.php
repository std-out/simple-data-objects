<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;
use StdOut\SimpleDataObjects\Support\Redactor;
use StdOut\SimpleDataObjects\ValueObjects\Money;

/**
 * Casts to/from `Money`, storing the amount in minor units (integer cents)
 * to avoid float rounding error. Accepts int minor units, a numeric decimal
 * string, or an `['amount' => ..., 'currency' => ...]` array on hydration;
 * serializes back to int minor units — the currency is fixed per field via
 * the constructor, so it isn't repeated in the serialized payload.
 */
final class MoneyCast implements CastsValue, ProvidesJsonSchema
{
    private const string MAX_MINOR_UNITS = '9223372036854775807';

    private const int MAX_DIGITS = 19;

    public function __construct(
        private readonly string $currency,
        private readonly int $decimals = 2,
    ) {}

    /** @param  array<string, mixed>  $state */
    public static function __set_state(array $state): self
    {
        return new self($state['currency'], $state['decimals']);
    }

    public function get(#[\SensitiveParameter] mixed $value): ?Money
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            $this->assertCurrency($value->currency);

            return $value;
        }

        if (is_array($value)) {
            $currency = $value['currency'] ?? $this->currency;
            $this->assertCurrency($currency);

            return new Money($this->resolveAmount($value['amount']), $currency);
        }

        return new Money($this->resolveAmount($value), $this->currency);
    }

    public function set(#[\SensitiveParameter] mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $this->assertCurrency($value->currency);

        return $value->amount;
    }

    private function resolveAmount(#[\SensitiveParameter] mixed $amount): int
    {
        if (is_int($amount)) {
            return $amount;
        }

        if (is_string($amount)) {
            return $this->parseDecimalString($amount);
        }

        throw new InvalidArgumentException(
            'MoneyCast amount must be an int (minor units) or a numeric decimal string, got '.get_debug_type($amount).'.',
        );
    }

    /**
     * Parses without ever going through float, so amounts like "1.005"
     * round deterministically instead of depending on binary float
     * representation (float gives 100 for "1.005" but 201 for "2.005",
     * despite both being an equally "exact" half-cent).
     */
    private function parseDecimalString(#[\SensitiveParameter] string $amount): int
    {
        $amount = trim($amount);

        if (! preg_match('/^[+-]?+(?:\d++\.?+\d*+|\.\d++)$/', $amount)) {
            throw new InvalidArgumentException('"'.Redactor::forMessage($amount).'" is not a valid decimal amount.');
        }

        $negative = $amount[0] === '-';
        $amount = ltrim($amount, '+-');

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        if (strlen($fraction) > $this->decimals) {
            $roundUp = $fraction[$this->decimals] >= '5';
            $fraction = substr($fraction, 0, $this->decimals);
        } else {
            $roundUp = false;
            $fraction = str_pad($fraction, $this->decimals, '0');
        }

        $digits = $whole.$fraction;

        if (strlen($digits) >= self::MAX_DIGITS) {
            $digits = ltrim($digits, '0');

            if (strlen($digits) > self::MAX_DIGITS
                || (strlen($digits) === self::MAX_DIGITS && strcmp($digits, self::MAX_MINOR_UNITS) > 0)
                || ($roundUp && $digits === self::MAX_MINOR_UNITS)) {
                throw new InvalidArgumentException('Decimal amount exceeds the supported range of minor units.');
            }
        }

        $minorUnits = (int) $digits + ($roundUp ? 1 : 0);

        return $negative ? -$minorUnits : $minorUnits;
    }

    private function assertCurrency(string $currency): void
    {
        if ($currency !== $this->currency) {
            throw new InvalidArgumentException(
                "Expected currency \"{$this->currency}\", got \"{$currency}\".",
            );
        }
    }

    /** @return array<string, mixed> */
    public function jsonSchema(): array
    {
        return ['type' => 'integer', 'description' => 'Amount in minor units (cents).'];
    }
}
