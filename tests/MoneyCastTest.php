<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\MoneyCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithMoneyData;
use StdOut\SimpleDataObjects\ValueObjects\Money;

class MoneyCastTest extends TestCase
{
    // Money value object

    public function test_money_stores_amount_and_currency(): void
    {
        $money = new Money(1099, 'USD');

        $this->assertSame(1099, $money->amount);
        $this->assertSame('USD', $money->currency);
    }

    public function test_money_to_decimal_defaults_to_two_places(): void
    {
        $this->assertSame('10.99', (new Money(1099, 'USD'))->toDecimal());
    }

    public function test_money_to_decimal_accepts_custom_decimals(): void
    {
        $this->assertSame('1099', (new Money(1099, 'JPY'))->toDecimal(0));
        $this->assertSame('1.099', (new Money(1099, 'BHD'))->toDecimal(3));
    }

    public function test_money_equals(): void
    {
        $this->assertTrue((new Money(1099, 'USD'))->equals(new Money(1099, 'USD')));
        $this->assertFalse((new Money(1099, 'USD'))->equals(new Money(1099, 'EUR')));
        $this->assertFalse((new Money(1099, 'USD'))->equals(new Money(500, 'USD')));
    }

    public function test_money_add(): void
    {
        $result = (new Money(1099, 'USD'))->add(new Money(1, 'USD'));

        $this->assertSame(1100, $result->amount);
        $this->assertSame('USD', $result->currency);
    }

    public function test_money_add_rejects_mismatched_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(1099, 'USD'))->add(new Money(1, 'EUR'));
    }

    public function test_money_subtract(): void
    {
        $result = (new Money(1099, 'USD'))->subtract(new Money(99, 'USD'));

        $this->assertSame(1000, $result->amount);
    }

    public function test_money_subtract_rejects_mismatched_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(1099, 'USD'))->subtract(new Money(1, 'EUR'));
    }

    public function test_money_to_string(): void
    {
        $this->assertSame('10.99 USD', (string) new Money(1099, 'USD'));
    }

    // MoneyCast::get()

    public function test_get_accepts_int_minor_units(): void
    {
        $money = (new MoneyCast('USD'))->get(1099);

        $this->assertSame(1099, $money->amount);
        $this->assertSame('USD', $money->currency);
    }

    public function test_get_accepts_decimal_string(): void
    {
        $money = (new MoneyCast('USD'))->get('10.99');

        $this->assertSame(1099, $money->amount);
    }

    public function test_get_accepts_array_with_matching_currency(): void
    {
        $money = (new MoneyCast('USD'))->get(['amount' => 1099, 'currency' => 'USD']);

        $this->assertSame(1099, $money->amount);
        $this->assertSame('USD', $money->currency);
    }

    public function test_get_array_without_currency_defaults_to_configured(): void
    {
        $money = (new MoneyCast('USD'))->get(['amount' => 1099]);

        $this->assertSame('USD', $money->currency);
    }

    public function test_get_array_with_mismatched_currency_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast('USD'))->get(['amount' => 1099, 'currency' => 'EUR']);
    }

    public function test_get_passes_through_matching_money_instance(): void
    {
        $input = new Money(1099, 'USD');

        $this->assertSame($input, (new MoneyCast('USD'))->get($input));
    }

    public function test_get_rejects_mismatched_money_instance(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast('USD'))->get(new Money(1099, 'EUR'));
    }

    public function test_get_returns_null_for_null(): void
    {
        $this->assertNull((new MoneyCast('USD'))->get(null));
    }

    public function test_get_rejects_non_numeric_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast('USD'))->get('not-a-number');
    }

    public function test_get_rejects_unsupported_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast('USD'))->get(10.99);
    }

    public function test_get_respects_custom_decimals(): void
    {
        $money = (new MoneyCast('JPY', decimals: 0))->get('1099');

        $this->assertSame(1099, $money->amount);
    }

    public function test_get_rounds_half_up_deterministically_without_float_drift(): void
    {
        // Binary float can't represent 1.005 or 2.005 exactly. A float-based
        // parser gives inconsistent results for these two equally-exact
        // half-cent amounts (100 vs 201); string-based parsing must not.
        $this->assertSame(101, (new MoneyCast('USD'))->get('1.005')->amount);
        $this->assertSame(201, (new MoneyCast('USD'))->get('2.005')->amount);
    }

    public function test_get_rounds_extra_fraction_digits(): void
    {
        $this->assertSame(2000, (new MoneyCast('USD'))->get('19.999')->amount);
        $this->assertSame(1999, (new MoneyCast('USD'))->get('19.994')->amount);
    }

    public function test_get_pads_short_fraction_with_zeros(): void
    {
        $this->assertSame(1090, (new MoneyCast('USD'))->get('10.9')->amount);
        $this->assertSame(1000, (new MoneyCast('USD'))->get('10')->amount);
    }

    public function test_get_accepts_leading_dot_decimal_string(): void
    {
        $this->assertSame(99, (new MoneyCast('USD'))->get('.99')->amount);
    }

    public function test_get_accepts_negative_decimal_string(): void
    {
        $this->assertSame(-1099, (new MoneyCast('USD'))->get('-10.99')->amount);
    }

    public function test_get_accepts_explicit_plus_sign(): void
    {
        $this->assertSame(1099, (new MoneyCast('USD'))->get('+10.99')->amount);
    }

    public function test_get_rejects_scientific_notation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast('USD'))->get('1e3');
    }

    public function test_get_handles_large_amounts_without_precision_loss(): void
    {
        $this->assertSame(10000000099, (new MoneyCast('USD'))->get('100000000.99')->amount);
    }

    // MoneyCast::set()

    public function test_set_serializes_to_int_minor_units(): void
    {
        $this->assertSame(1099, (new MoneyCast('USD'))->set(new Money(1099, 'USD')));
    }

    public function test_set_returns_null_for_null(): void
    {
        $this->assertNull((new MoneyCast('USD'))->set(null));
    }

    public function test_set_rejects_mismatched_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast('USD'))->set(new Money(1099, 'EUR'));
    }

    // __set_state

    public function test_set_state_restores_instance(): void
    {
        $cast = MoneyCast::__set_state(['currency' => 'USD', 'decimals' => 2]);

        $this->assertSame(1099, $cast->get('10.99')->amount);
    }

    // Hydration / serialization via DTO

    public function test_hydrates_and_serializes_via_dto(): void
    {
        $order = OrderWithMoneyData::from(['price' => '10.99']);

        $this->assertInstanceOf(Money::class, $order->price);
        $this->assertSame(1099, $order->price->amount);
        $this->assertSame('USD', $order->price->currency);
        $this->assertSame(1099, $order->toArray()['price']);
    }
}
