<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\BooleanCast;
use StdOut\SimpleDataObjects\Casts\CommaSeparatedCast;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;
use StdOut\SimpleDataObjects\Casts\FloatCast;
use StdOut\SimpleDataObjects\Casts\IntegerCast;
use StdOut\SimpleDataObjects\Casts\MoneyCast;
use StdOut\SimpleDataObjects\Casts\TrimCast;
use StdOut\SimpleDataObjects\Casts\UuidCast;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\CollectingEdgeCasesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithUuidData;

class CastFailureTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    /** @return iterable<string, array{CastsValue, mixed, string}> */
    public static function wrongTypes(): iterable
    {
        yield 'BooleanCast' => [new BooleanCast, ['x'], 'BooleanCast expects a string, got array.'];
        yield 'CommaSeparatedCast' => [new CommaSeparatedCast, new \stdClass, 'CommaSeparatedCast expects a string, got stdClass.'];
        yield 'DateTimeCast' => [new DateTimeCast, ['x'], 'DateTimeCast expects a string, got array.'];
        yield 'DateTimeImmutableCast' => [new DateTimeImmutableCast, true, 'DateTimeImmutableCast expects a string, got bool.'];
        yield 'TrimCast' => [new TrimCast, ['x'], 'TrimCast expects a string, got array.'];
        yield 'UuidCast' => [new UuidCast, ['x'], 'UuidCast expects a string, got array.'];
        yield 'IntegerCast' => [new IntegerCast, ['x'], 'IntegerCast expects a scalar, got array.'];
        yield 'FloatCast' => [new FloatCast, new \stdClass, 'FloatCast expects a scalar, got stdClass.'];
        yield 'MoneyCast without amount' => [new MoneyCast('USD'), ['currency' => 'USD'], 'MoneyCast amount must be an int (minor units) or a numeric decimal string, got null.'];
    }

    #[DataProvider('wrongTypes')]
    public function test_casts_reject_a_wrong_input_type_without_a_php_error(CastsValue $cast, mixed $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $cast->get($value);
    }

    public function test_string_casts_accept_scalars_and_stringables(): void
    {
        $value = new class implements \Stringable
        {
            public function __toString(): string
            {
                return '  Text ';
            }
        };

        $this->assertSame('Text', new TrimCast()->get($value));
        $this->assertSame('12', new TrimCast()->get(12));
        $this->assertSame('1.5', new TrimCast()->get(1.5));
    }

    public function test_cast_failure_is_a_hydration_exception_naming_the_field(): void
    {
        try {
            OrderWithUuidData::from(['id' => ['x']]);
            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertSame("Invalid value for field 'id': UuidCast expects a string, got array.", $e->getMessage());
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
        }

        $this->assertNull(OrderWithUuidData::tryFrom(['id' => ['x']]));
        $this->assertSame(['id' => "Invalid value for field 'id': UuidCast expects a string, got array."], OrderWithUuidData::fromResult(['id' => ['x']])->errors());
    }

    public function test_with_reports_a_cast_failure_the_same_way(): void
    {
        $order = OrderWithUuidData::from(['id' => '123e4567-e89b-12d3-a456-426614174000']);

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Invalid value for field 'id'");

        $order->with(id: 'not-a-uuid');
    }

    public function test_collection_of_a_non_iterable_value_is_rejected_without_internal_paths(): void
    {
        try {
            CollectingEdgeCasesData::from(['required' => ['street' => 's', 'city' => 'c'], 'nullable' => null, 'requiredItems' => 5, 'nullableItems' => null]);
            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertSame("Invalid value for field 'requiredItems': A data collection expects an array, got int.", $e->getMessage());
        }
    }

    public function test_nested_hydration_errors_are_not_wrapped_again(): void
    {
        try {
            CollectingEdgeCasesData::from(['required' => ['street' => 's'], 'nullable' => null, 'requiredItems' => [], 'nullableItems' => null]);
            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertStringStartsWith("Missing required field 'city'", $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
    }

    public function test_messages_strip_php_call_sites_and_mask_card_numbers(): void
    {
        $e = DataHydrationException::invalidValue('card', new \TypeError('Argument #1 must be of type int, string 4111111111111111 given, called in /app/x.php on line 3'));

        $this->assertSame("Invalid value for field 'card': Argument #1 must be of type int, string ************1111 given", $e->getMessage());
    }
}
