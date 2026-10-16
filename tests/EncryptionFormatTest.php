<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyring;
use StdOut\SimpleDataObjects\Support\BlindIndex;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConfigurableKeyring;
use StdOut\SimpleDataObjects\Tests\Fixtures\KeyringCardData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TestKeyring;

class EncryptionFormatTest extends TestCase
{
    private const int BASE64 = SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

    private static function subkey(string $master, string $context): string
    {
        return sodium_crypto_kdf_derive_from_key(32, 1, $context, $master);
    }

    private static function seal(string $plaintext, string $associatedData): string
    {
        $nonce = random_bytes(24);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            sodium_pad($plaintext, 32),
            'v2:2026:'.$associatedData,
            $nonce,
            self::subkey(str_repeat("\x02", 32), 'SDOcrypt'),
        );

        return 'v2:2026:'.sodium_bin2base64($nonce.$ciphertext, self::BASE64);
    }

    private static function open(string $value, string $associatedData): string|false
    {
        $decoded = sodium_base642bin(substr($value, strlen('v2:2026:')), self::BASE64);
        $padded = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($decoded, 24),
            'v2:2026:'.$associatedData,
            substr($decoded, 0, 24),
            self::subkey(str_repeat("\x02", 32), 'SDOcrypt'),
        );

        return $padded === false ? false : sodium_unpad($padded, 32);
    }

    public function test_dto_reads_values_built_from_the_documented_keyring_construction(): void
    {
        $card = KeyringCardData::from([
            'pan' => self::seal('4111111111111111', KeyringCardData::class.'::pan'),
            'holder' => self::seal('Alice', KeyringCardData::class.'::holder'),
            'note' => self::seal('vip', 'cards.note'),
        ]);

        $this->assertSame(['4111111111111111', 'Alice', 'vip'], [$card->pan, $card->holder, $card->note]);
    }

    public function test_dto_writes_values_that_follow_the_documented_keyring_construction(): void
    {
        $row = (new KeyringCardData('4111111111111111', 'Alice', 'vip'))->toArray();

        $this->assertStringStartsWith('v2:2026:', $row['pan']);
        $this->assertSame('4111111111111111', self::open($row['pan'], KeyringCardData::class.'::pan'));
        $this->assertSame('Alice', self::open($row['holder'], KeyringCardData::class.'::holder'));
        $this->assertSame('vip', self::open($row['note'], 'cards.note'));
        $this->assertSame(24 + 32 + 16, strlen(sodium_base642bin(substr($row['pan'], 8), self::BASE64)));
    }

    public function test_explicit_context_is_used_even_when_the_cast_is_unbound(): void
    {
        $cast = new EncryptedCast(keyring: TestKeyring::class, context: 'cards.note');

        $this->assertSame('vip', self::open($cast->set('vip'), 'cards.note'));
    }

    public function test_legacy_format_matches_the_documented_construction(): void
    {
        $key = sodium_crypto_generichash('old-key', '', 32);
        $nonce = random_bytes(24);
        $legacy = sodium_bin2base64($nonce.sodium_crypto_secretbox('secret', $nonce, $key), self::BASE64);
        $written = sodium_base642bin((new EncryptedCast('old-key'))->set('secret'), self::BASE64);

        $this->assertSame('secret', (new EncryptedCast('old-key'))->get($legacy));
        $this->assertSame('secret', sodium_crypto_secretbox_open(substr($written, 24), substr($written, 0, 24), $key));
    }

    public function test_legacy_value_of_exactly_one_nonce_is_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Encrypted value is too short.');

        (new EncryptedCast('old-key'))->get(sodium_bin2base64(str_repeat('x', 24), self::BASE64));
    }

    public function test_payload_containing_a_colon_is_not_valid_base64(): void
    {
        ConfigurableKeyring::$current = 'k1';
        ConfigurableKeyring::$keys = ['k1' => str_repeat("\x03", 32)];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Encrypted value is not valid base64.');

        (new EncryptedCast(keyring: ConfigurableKeyring::class))->get('v2:k1:abc:def');
    }

    public function test_needs_reencryption_compares_the_whole_key_id(): void
    {
        ConfigurableKeyring::$current = 'k1';
        ConfigurableKeyring::$keys = ['k1' => str_repeat("\x03", 32), 'k10' => str_repeat("\x04", 32)];

        $this->assertTrue((new EncryptedCast(keyring: ConfigurableKeyring::class))->needsReencryption('v2:k10:AAAA'));
    }

    public function test_blind_index_matches_the_documented_construction(): void
    {
        $key = str_repeat("\x05", 32);
        $expected = sodium_bin2base64(
            hash_hmac('sha256', pack('N', 9).'cards.pan'.'4111111111111111', self::subkey($key, 'SDOindex'), true),
            self::BASE64,
        );

        $this->assertSame($expected, (new BlindIndex($key, context: 'cards.pan'))->hash('4111111111111111'));
    }

    public function test_error_messages_name_the_expected_contract(): void
    {
        $messages = [];

        foreach ([
            static fn () => new EncryptedCast(keyring: \stdClass::class),
            static fn () => new EncryptedCast(provider: \stdClass::class),
            static fn () => (new EncryptedCast('k'))->set([]),
        ] as $call) {
            try {
                $call();
            } catch (InvalidArgumentException $e) {
                $messages[] = $e->getMessage();
            }
        }

        $this->assertSame([
            "Keyring 'stdClass' must implement ".EncryptionKeyring::class.'.',
            "Key provider 'stdClass' must implement ".EncryptionKeyProvider::class.'.',
            'EncryptedCast can only encrypt scalar or Stringable values, got array.',
        ], $messages);
    }

    public function test_tampering_is_reported_as_an_authentication_failure(): void
    {
        $value = self::seal('Alice', KeyringCardData::class.'::pan');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed: authentication tag mismatch.');

        KeyringCardData::from(['pan' => $value, 'holder' => $value]);
    }
}
