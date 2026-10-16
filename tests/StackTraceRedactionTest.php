<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Closure;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Casts\UuidCast;
use StdOut\SimpleDataObjects\Laravel\DataCollectionCast;
use StdOut\SimpleDataObjects\Laravel\DataObjectCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableAddressData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableItemData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithMoneyData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithUuidData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveCardData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowingPipeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ValidatedUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\WireableEventData;
use Throwable;

class StackTraceRedactionTest extends TestCase
{
    private const string PAN = '4111111111111111';

    private string|false $ignoreArgs;

    protected function setUp(): void
    {
        $this->ignoreArgs = ini_set('zend.exception_ignore_args', '0');
    }

    protected function tearDown(): void
    {
        ini_set('zend.exception_ignore_args', (string) $this->ignoreArgs);
    }

    /** @return iterable<string, array{Closure(): mixed}> */
    public static function failingCalls(): iterable
    {
        $pan = self::PAN;

        yield 'from array' => [static fn () => SensitiveCardData::from(['pan' => $pan])];
        yield 'from JSON string' => [static fn () => SensitiveCardData::from('{"pan":"'.$pan.'"')];
        yield 'fromJson' => [static fn () => SensitiveCardData::fromJson('{"pan":"'.$pan.'"}')];
        yield 'from object' => [static fn () => SensitiveCardData::from((object) ['pan' => $pan])];
        yield 'fromLazy' => [static fn () => SensitiveCardData::fromLazy(['pan' => $pan])->holder];
        yield 'fromValidated' => [static fn () => ValidatedUserData::fromValidated(['name' => 'a', 'email' => $pan])];
        yield 'validate' => [static fn () => ValidatedUserData::validate(['name' => 'a', 'email' => $pan])];
        yield 'with' => [static fn () => SensitiveCardData::from(['holder' => 'a', 'pan' => $pan])->with(status: $pan)];
        yield 'collection' => [static fn () => SensitiveCardData::collection([['pan' => $pan]])];
        yield 'lazyCollection' => [static fn () => SensitiveCardData::lazyCollection([['pan' => $pan]])->all()];
        yield 'cast via DTO' => [static fn () => OrderWithUuidData::from(['id' => $pan])];
        yield 'money cast' => [static fn () => OrderWithMoneyData::from(['price' => $pan.'x'])];
        yield 'cast directly' => [static fn () => (new UuidCast)->get($pan)];
        yield 'date cast' => [static fn () => (new DateTimeCast)->get($pan)];
        yield 'immutable date cast' => [static fn () => (new DateTimeImmutableCast)->get($pan)];
        yield 'encrypted cast' => [static fn () => (new EncryptedCast('k'))->get($pan.'!')];
        yield 'data pipe' => [static fn () => ThrowingPipeData::from(['name' => $pan])];
        yield 'fromLivewire' => [static fn () => WireableEventData::fromLivewire(['name' => $pan])];
    }

    #[DataProvider('failingCalls')]
    public function test_input_never_appears_in_stack_trace_arguments(Closure $call): void
    {
        $this->assertNoPanInTrace($call);
    }

    public function test_eloquent_casts_keep_input_out_of_stack_trace_arguments(): void
    {
        $model = $this->createMock(Model::class);
        $attributes = ['card' => self::PAN];

        $this->assertNoPanInTrace(fn () => (new DataObjectCast(CastableAddressData::class))->get($model, 'address', ['street' => self::PAN], $attributes));
        $this->assertNoPanInTrace(fn () => (new DataCollectionCast(CastableItemData::class))->get($model, 'items', '{"pan":"'.self::PAN.'"', $attributes));
    }

    private function assertNoPanInTrace(Closure $call): void
    {
        try {
            $call();
        } catch (Throwable $e) {
            for ($current = $e; $current !== null; $current = $current->getPrevious()) {
                foreach ($current->getTrace() as $frame) {
                    $this->assertFalse(
                        self::containsPan($frame['args'] ?? []),
                        'PAN leaked through arguments of '.($frame['class'] ?? '').($frame['type'] ?? '').$frame['function'],
                    );
                }
            }

            return;
        }

        $this->fail('Expected the call to throw.');
    }

    private static function containsPan(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (self::containsPan($key) || self::containsPan($item)) {
                    return true;
                }
            }

            return false;
        }

        return is_scalar($value) && str_contains((string) $value, self::PAN);
    }
}
