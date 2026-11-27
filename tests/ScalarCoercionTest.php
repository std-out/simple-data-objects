<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridChargeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ScalarData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ScalarPropertiesData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowsTypeErrorData;

class ScalarCoercionTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
    }

    /** @return iterable<string, array{string, mixed, mixed}> */
    public static function losslessValues(): iterable
    {
        yield 'int from int' => ['int', 5, 5];
        yield 'int from whole float' => ['int', 5.0, 5];
        yield 'int from digit string' => ['int', '-42', -42];
        yield 'int from the smallest int as float' => ['int', -9.2233720368547758E18, PHP_INT_MIN];
        yield 'float from float' => ['float', 1.5, 1.5];
        yield 'float from int' => ['float', 2, 2.0];
        yield 'float from numeric string' => ['float', '1.25', 1.25];
        yield 'string from string' => ['string', 'abc', 'abc'];
        yield 'string from int' => ['string', 7, '7'];
        yield 'string from float' => ['string', 1.5, '1.5'];
        yield 'string from stringable' => ['string', new class implements \Stringable
        {
            public function __toString(): string
            {
                return 'text';
            }
        }, 'text'];
        yield 'bool from bool' => ['bool', true, true];
        yield 'bool from "false"' => ['bool', 'false', false];
        yield 'bool from "no"' => ['bool', 'no', false];
        yield 'bool from "on"' => ['bool', 'on', true];
        yield 'bool from empty string' => ['bool', '', false];
        yield 'bool from 1' => ['bool', 1, true];
        yield 'bool from 0' => ['bool', 0, false];
    }

    #[DataProvider('losslessValues')]
    public function test_lossless_values_are_accepted(string $field, mixed $input, mixed $expected): void
    {
        $this->assertSame($expected, ScalarData::from([$field => $input])->{$field});
        $this->assertSame($expected, ScalarData::fromResult([$field => $input])->value()->{$field});
        $this->assertSame($expected, ScalarData::from([])->with(...[$field => $input])->{$field});
    }

    /** @return iterable<string, array{string, mixed, string}> */
    public static function lossyValues(): iterable
    {
        yield 'int from decimal string' => ['int', '1.9', 'string'];
        yield 'int from fractional float' => ['int', 1.9, 'float'];
        yield 'int from huge float' => ['int', 1e19, 'float'];
        yield 'int from very negative float' => ['int', -1e19, 'float'];
        yield 'int from 2 ** 63 as float' => ['int', 9.2233720368547758E18, 'float'];
        yield 'int from bool' => ['int', true, 'bool'];
        yield 'int from text' => ['int', 'abc', 'string'];
        yield 'int from overflowing string' => ['int', '99999999999999999999', 'string'];
        yield 'int from null' => ['int', null, 'null'];
        yield 'float from bool' => ['float', true, 'bool'];
        yield 'float from text' => ['float', 'abc', 'string'];
        yield 'float from overflowing string' => ['float', '1e400', 'string'];
        yield 'string from bool' => ['string', true, 'bool'];
        yield 'string from array' => ['string', ['x'], 'array'];
        yield 'bool from "maybe"' => ['bool', 'maybe', 'string'];
        yield 'bool from 2' => ['bool', 2, 'int'];
        yield 'bool from float' => ['bool', 1.0, 'float'];
    }

    #[DataProvider('lossyValues')]
    public function test_lossy_values_throw_a_hydration_exception_naming_the_field(string $field, mixed $input, string $given): void
    {
        $message = "Invalid value for field '{$field}': Cannot convert {$given} to {$field} without loss.";

        $this->assertSame([$field => $message], ScalarData::fromResult([$field => $input])->errors());
        $this->assertNull(ScalarData::tryFrom([$field => $input]));

        try {
            ScalarData::from([])->with(...[$field => $input]);
            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertSame($message, $e->getMessage());
        }

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage($message);

        ScalarData::from([$field => $input]);
    }

    public function test_nullable_scalars_keep_null_and_coerce_other_values(): void
    {
        $data = ScalarData::from(['nullableInt' => null, 'is_flag' => 'yes', 'piped' => ' 7 ']);

        $this->assertNull($data->nullableInt);
        $this->assertTrue($data->flag);
        $this->assertSame(7, $data->piped);
        $this->assertNull(ScalarData::from(['piped' => null])->piped);
        $this->assertSame(3, ScalarData::from(['nullableInt' => '3'])->nullableInt);
        $this->assertNull(ScalarData::from([])->with(nullableInt: null)->nullableInt);
        $this->assertSame(['piped'], array_keys(ScalarData::fromResult(['piped' => ' 1.5 '])->errors()));
    }

    public function test_union_types_are_left_to_php(): void
    {
        $this->assertSame('abc', ScalarData::from(['union' => 'abc'])->union);
        $this->assertNull(MetadataRegistry::get(ScalarData::class)->parameters[8]->scalarType);
    }

    public function test_typed_properties_are_coerced_the_same_way(): void
    {
        $data = ScalarPropertiesData::from(['count' => '12', 'ratio' => '0.5']);

        $this->assertSame([12, 0.5], [$data->count, $data->ratio]);
        $this->assertNull(ScalarPropertiesData::from(['count' => 1])->ratio);
        $this->assertSame(['count'], array_keys(ScalarPropertiesData::fromResult(['count' => '1.5'])->errors()));

        $this->expectException(DataHydrationException::class);

        ScalarPropertiesData::from(['count' => 1, 'ratio' => 'abc']);
    }

    public function test_hybrid_classes_coerce_constructor_arguments_and_keep_extra_properties(): void
    {
        $data = HybridChargeData::from(['pan' => 4111, 'amount' => '5', 'reference' => 'r']);

        $this->assertSame(['4111', 5, 'r'], [$data->pan, $data->amount, $data->reference]);
    }

    public function test_cast_properties_are_left_to_their_cast(): void
    {
        $this->assertSame([1, 5], [ScalarData::from(['casted' => '1.9', 'int' => '5'])->casted, ScalarData::from(['int' => '5'])->int]);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function typeErrorInputs(): iterable
    {
        yield 'already fitting' => [['count' => 5, 'ratio' => 1]];
        yield 'coerced first' => [['count' => '5']];
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('typeErrorInputs')]
    public function test_a_type_error_from_the_constructor_body_is_rethrown_without_running_it_twice(array $input): void
    {
        ThrowsTypeErrorData::$calls = 0;

        try {
            ThrowsTypeErrorData::from($input);
            $this->fail('Expected a TypeError.');
        } catch (\TypeError $e) {
            $this->assertSame('Count rejected.', $e->getMessage());
        }

        $this->assertSame(1, ThrowsTypeErrorData::$calls);
    }
}
