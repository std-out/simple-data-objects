<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConfigurableKeyring;
use StdOut\SimpleDataObjects\Tests\Fixtures\KeyringCardData;
use StdOut\SimpleDataObjects\Tests\Fixtures\KeyringCardSubclassData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TestKeyring;

class EncryptedKeyringTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigurableKeyring::$current = 'k1';
        ConfigurableKeyring::$keys = ['k1' => base64_encode(str_repeat("\x03", 32))];
    }

    private static function ring(): EncryptedCast
    {
        return new EncryptedCast(keyring: ConfigurableKeyring::class);
    }

    public function test_round_trips_in_the_keyring_format(): void
    {
        $cast = self::ring();
        $sealed = $cast->set('4111111111111111');

        $this->assertMatchesRegularExpression('/^v2:k1:[A-Za-z0-9_-]+$/', $sealed);
        $this->assertSame('4111111111111111', $cast->get($sealed));
        $this->assertNotSame($sealed, $cast->set('4111111111111111'));
    }

    public function test_pads_plaintext_so_card_lengths_are_indistinguishable(): void
    {
        $cast = self::ring();
        $lengths = array_map(static fn (string $pan): int => strlen($cast->set($pan)), ['4111111111111', '4111111111111111', '4111111111111111111', '']);

        $this->assertCount(1, array_unique($lengths));
    }

    public function test_reads_older_keys_and_writes_with_the_current_one(): void
    {
        $old = self::ring()->set('secret');

        ConfigurableKeyring::$keys['k2'] = str_repeat("\x04", 32);
        ConfigurableKeyring::$current = 'k2';
        $rotated = self::ring();

        $this->assertSame('secret', $rotated->get($old));
        $this->assertTrue($rotated->needsReencryption($old));
        $this->assertStringStartsWith('v2:k2:', $rotated->set('secret'));
        $this->assertFalse($rotated->needsReencryption($rotated->set('secret')));
    }

    public function test_reads_legacy_ciphertext_when_a_legacy_key_is_configured(): void
    {
        $legacy = (new EncryptedCast('old-key'))->set('secret');
        $cast = new EncryptedCast('old-key', keyring: ConfigurableKeyring::class);

        $this->assertSame('secret', $cast->get($legacy));
        $this->assertTrue($cast->needsReencryption($legacy));
        $this->assertStringStartsWith('v2:k1:', $cast->set('secret'));
        $this->assertFalse((new EncryptedCast('old-key'))->needsReencryption($legacy));
    }

    public function test_legacy_ciphertext_without_a_legacy_key_throws(): void
    {
        $legacy = (new EncryptedCast('old-key'))->set('secret');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('legacy format');

        self::ring()->get($legacy);
    }

    public function test_keyring_ciphertext_without_a_keyring_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no $keyring is configured');

        (new EncryptedCast('old-key'))->get(self::ring()->set('secret'));
    }

    public function test_unknown_key_id_throws(): void
    {
        $sealed = self::ring()->set('secret');
        ConfigurableKeyring::$keys = ['k9' => str_repeat("\x05", 32)];
        ConfigurableKeyring::$current = 'k9';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unknown encryption key id 'k1'.");

        self::ring()->get($sealed);
    }

    public function test_ciphertext_is_bound_to_its_field(): void
    {
        $cast = self::ring();
        $pan = $cast->forField(KeyringCardData::class, 'pan');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('authentication tag mismatch');

        $cast->forField(KeyringCardData::class, 'holder')->get($pan->set('4111111111111111'));
    }

    public function test_swapping_ciphertexts_between_fields_of_a_dto_is_rejected(): void
    {
        $card = (new KeyringCardData('4111111111111111', 'Alice'))->toArray();

        $this->assertSame('4111111111111111', KeyringCardData::from($card)->pan);
        $this->assertSame('Alice', KeyringCardSubclassData::from($card)->holder);

        $this->expectException(RuntimeException::class);

        KeyringCardData::from(['pan' => $card['holder'], 'holder' => $card['pan']]);
    }

    public function test_explicit_context_survives_field_binding(): void
    {
        $sealed = (new KeyringCardData('1', 'a', 'n'))->toArray()['note'];
        $cast = new EncryptedCast(keyring: TestKeyring::class, context: 'cards.note');

        $this->assertSame('n', $cast->get($sealed));
        $this->assertSame($cast, $cast->forField(KeyringCardData::class, 'note'));
    }

    public function test_unbound_cast_cannot_read_a_field_bound_value(): void
    {
        $sealed = (new KeyringCardData('4111111111111111', 'a'))->toArray()['pan'];
        $cast = new EncryptedCast(keyring: TestKeyring::class);

        $this->assertSame('4111111111111111', $cast->forField(KeyringCardData::class, 'pan')->get($sealed));

        $this->expectException(RuntimeException::class);
        $cast->get($sealed);
    }

    public function test_tampered_key_id_is_rejected(): void
    {
        ConfigurableKeyring::$keys['k2'] = ConfigurableKeyring::$keys['k1'];
        $sealed = self::ring()->set('secret');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('authentication tag mismatch');

        self::ring()->get('v2:k2:'.substr($sealed, 6));
    }

    /** @return iterable<string, array{string, class-string<\Throwable>, string}> */
    public static function malformedValues(): iterable
    {
        yield 'missing payload' => ['v2:k1', InvalidArgumentException::class, 'malformed'];
        yield 'not base64' => ['v2:k1:!!!', InvalidArgumentException::class, 'not valid base64'];
        yield 'too short' => ['v2:k1:'.sodium_bin2base64(str_repeat('x', 40), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING), InvalidArgumentException::class, 'too short'];
        yield 'forged' => ['v2:k1:'.sodium_bin2base64(str_repeat('x', 41), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING), RuntimeException::class, 'authentication tag mismatch'];
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProvider('malformedValues')]
    public function test_malformed_keyring_values_are_rejected(string $value, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        self::ring()->get($value);
    }

    /** @return iterable<string, array{array<array-key, string>, string, string}> */
    public static function invalidKeyrings(): iterable
    {
        $key = str_repeat("\x06", 32);

        yield 'short key' => [['k1' => 'too-short'], 'k1', "Key 'k1' of ".ConfigurableKeyring::class.' must be 32 random bytes'];
        yield 'printable 32-character key' => [['k1' => str_repeat('a', 32)], 'k1', 'must be 32 random bytes'];
        yield 'key id with a colon' => [['k:1' => $key], 'k:1', "Key id 'k:1'"];
        yield 'current key missing' => [['k1' => $key], 'k2', "Current key id 'k2' is not among the keys"];
    }

    /** @param array<array-key, string> $keys */
    #[DataProvider('invalidKeyrings')]
    public function test_invalid_keyrings_are_rejected(array $keys, string $current, string $message): void
    {
        ConfigurableKeyring::$keys = $keys;
        ConfigurableKeyring::$current = $current;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        self::ring();
    }

    public function test_integer_key_ids_are_accepted(): void
    {
        ConfigurableKeyring::$keys = [7 => str_repeat("\x07", 32)];
        ConfigurableKeyring::$current = '7';

        $this->assertStringStartsWith('v2:7:', self::ring()->set('x'));
    }

    public function test_url_safe_base64_keys_are_accepted(): void
    {
        $raw = str_repeat("\xfb", 32);
        ConfigurableKeyring::$keys = ['k1' => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=')];
        $sealed = self::ring()->set('x');

        ConfigurableKeyring::$keys = ['k1' => $raw];

        $this->assertSame('x', self::ring()->get($sealed));
    }

    public function test_class_that_is_not_a_keyring_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Keyring 'stdClass' must implement");

        new EncryptedCast(keyring: \stdClass::class);
    }

    public function test_no_key_source_at_all_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provide $keyring, or exactly one of $key, $env or $provider.');

        new EncryptedCast(context: 'x');
    }

    public function test_keyring_keys_never_appear_in_dumps(): void
    {
        $cast = self::ring();
        $dump = var_export($cast, true).print_r((array) $cast, true).print_r($cast, true);

        $this->assertStringNotContainsString(str_repeat("\x03", 32), $dump);
        $this->assertStringNotContainsString(ConfigurableKeyring::$keys['k1'], $dump);
    }
}
