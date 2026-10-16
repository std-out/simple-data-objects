<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\BlindIndex;

class BlindIndexTest extends TestCase
{
    private const string KEY = "\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f\x20";

    public function test_hash_is_deterministic_url_safe_and_hides_the_value(): void
    {
        $index = new BlindIndex(self::KEY, context: 'cards.pan');
        $hash = $index->hash('4111111111111111');

        $this->assertSame($hash, (new BlindIndex(base64_encode(self::KEY), context: 'cards.pan'))->hash('4111111111111111'));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $hash);
        $this->assertStringNotContainsString('1111', $hash);
        $this->assertNotSame($hash, $index->hash('4111111111111112'));
    }

    public function test_context_separates_indexes(): void
    {
        $this->assertNotSame(
            (new BlindIndex(self::KEY, context: 'cards.pan'))->hash('4111111111111111'),
            (new BlindIndex(self::KEY, context: 'cards.pan2'))->hash('4111111111111111'),
        );
        $this->assertNotSame(
            (new BlindIndex(self::KEY, context: 'a'))->hash('bc'),
            (new BlindIndex(self::KEY, context: 'ab'))->hash('c'),
        );
    }

    public function test_matches_compares_in_constant_time(): void
    {
        $index = new BlindIndex(self::KEY);

        $this->assertTrue($index->matches('4111111111111111', $index->hash('4111111111111111')));
        $this->assertFalse($index->matches('4111111111111112', $index->hash('4111111111111111')));
    }

    public function test_key_is_read_from_the_environment(): void
    {
        $_ENV['SDO_TEST_INDEX_KEY'] = base64_encode(self::KEY);

        try {
            $this->assertSame((new BlindIndex(self::KEY))->hash('x'), (new BlindIndex(env: 'SDO_TEST_INDEX_KEY'))->hash('x'));
        } finally {
            unset($_ENV['SDO_TEST_INDEX_KEY']);
        }
    }

    public function test_weak_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Blind index key must be 32 random bytes');

        new BlindIndex('password');
    }

    public function test_a_key_source_is_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provide exactly one of $key, $env or $provider.');

        new BlindIndex(context: 'x');
    }

    public function test_key_never_appears_in_dumps_or_serialization(): void
    {
        $index = new BlindIndex(self::KEY);
        $dump = var_export($index, true).print_r((array) $index, true).print_r($index, true);

        $this->assertStringNotContainsString(self::KEY, $dump);
        $this->assertStringContainsString('[redacted]', print_r($index, true));

        $this->expectException(LogicException::class);
        serialize($index);
    }
}
