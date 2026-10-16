<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;
use StdOut\SimpleDataObjects\Casts\MoneyCast;
use StdOut\SimpleDataObjects\Casts\UuidCast;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\HydrationResult;
use StdOut\SimpleDataObjects\Support\Redactor;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictUserData;

class PanMaskingTest extends TestCase
{
    private const string PAN = '4111111111111111';

    private const string MASKED = '************1111';

    public function test_masks_all_but_the_last_four_digits(): void
    {
        $this->assertSame(self::MASKED, Redactor::maskPan(self::PAN));
        $this->assertSame('*********0004', Redactor::maskPan('4222222220004'));
        $this->assertSame('***************1234', Redactor::maskPan('6011000990139421234'));
    }

    public function test_masks_separated_and_embedded_numbers(): void
    {
        $this->assertSame('**** **** **** 1111', Redactor::maskPan('4111 1111 1111 1111'));
        $this->assertSame('****-****-****-1111', Redactor::maskPan('4111-1111-1111-1111'));
        $this->assertSame('card x************1111y declined', Redactor::maskPan('card x4111111111111111y declined'));
        $this->assertSame('a ************1111, b ************4444', Redactor::maskPan('a 4111111111111111, b 5555555555554444'));
    }

    public function test_leaves_other_numbers_untouched(): void
    {
        foreach (['', 'short', '123456789012', '12345678901234567890', '4111.1111.1111.1111', 'UA903052992990004149123456789'] as $value) {
            $this->assertSame($value, Redactor::maskPan($value));
        }
    }

    public function test_for_message_masks_and_escapes_control_characters(): void
    {
        $this->assertSame('\n'.self::MASKED, Redactor::forMessage("\n".self::PAN));
    }

    public function test_exception_messages_mask_pans(): void
    {
        $this->assertSame(
            "Invalid value '".self::MASKED."' for field 'brand'; expected a case of ".Status::class.'.',
            DataHydrationException::invalidEnumValue(Status::class, 'brand', self::PAN)->getMessage(),
        );

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Unknown discriminator value '".self::MASKED."' in field 'type'");

        PaymentMethodData::from(['type' => self::PAN]);
    }

    public function test_unknown_keys_are_masked_in_the_message_but_kept_on_the_exception(): void
    {
        try {
            StrictUserData::from(['name' => 'Alice', self::PAN => 'x']);
            $this->fail('Expected DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertStringContainsString("['".self::MASKED."']", $e->getMessage());
            $this->assertSame([(int) self::PAN], $e->unknownKeys);
        }
    }

    public function test_cast_messages_mask_pans(): void
    {
        $casts = [
            [new UuidCast, '"'.self::MASKED.'" is not a valid RFC 4122 UUID.'],
            [new MoneyCast('USD'), '"'.self::MASKED.'x" is not a valid decimal amount.'],
            [new DateTimeCast(inputFormat: 'Y-m-d'), 'Cannot parse "'.self::MASKED.'x" using format "Y-m-d".'],
            [new DateTimeImmutableCast(inputFormat: 'Y-m-d'), 'Cannot parse "'.self::MASKED.'x" using format "Y-m-d".'],
        ];

        foreach ($casts as [$cast, $message]) {
            try {
                $cast->get($cast instanceof UuidCast ? self::PAN : self::PAN.'x');
                $this->fail('Expected InvalidArgumentException.');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    public function test_native_date_parse_errors_mask_pans_and_keep_their_type(): void
    {
        foreach ([new DateTimeCast, new DateTimeImmutableCast] as $cast) {
            try {
                $cast->get(self::PAN);
                $this->fail('Expected DateMalformedStringException.');
            } catch (\DateMalformedStringException $e) {
                $this->assertStringContainsString(self::MASKED, $e->getMessage());
                $this->assertStringNotContainsString(self::PAN, $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
    }

    public function test_failure_masks_every_collected_message(): void
    {
        $result = HydrationResult::failure(['pan' => 'Rejected '.self::PAN.'.', 'name' => 'Required.']);

        $this->assertSame(['pan' => 'Rejected '.self::MASKED.'.', 'name' => 'Required.'], $result->errors());
    }

    public function test_from_result_errors_mask_pans(): void
    {
        $result = PaymentMethodData::fromResult(['type' => self::PAN]);

        $this->assertStringContainsString(self::MASKED, $result->errors()['type']);
        $this->assertStringNotContainsString(self::PAN, $result->errors()['type']);
    }
}
