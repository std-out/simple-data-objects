<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use RuntimeException;
use SodiumException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;

/**
 * Authenticated encryption using XSalsa20-Poly1305 (libsodium).
 *
 * Breaking change from previous AES-256-CBC version: existing ciphertext
 * produced by the old cast is not compatible and must be re-encrypted.
 */
final class EncryptedCast implements CastsValue
{
    public readonly string $key;

    private readonly string $secretKey;

    public function __construct(string $key)
    {
        $this->key = $key;
        // BLAKE2b: a secure, fast KDF — no brute-force shortcut unlike raw sha256
        $this->secretKey = sodium_crypto_generichash($key, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public static function __set_state(array $state): self
    {
        return new self($state['key']);
    }

    public function get(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            $decoded = sodium_base642bin((string) $value, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        } catch (SodiumException) {
            throw new InvalidArgumentException('Encrypted value is not valid base64.');
        }

        if (strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new InvalidArgumentException('Encrypted value is too short.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->secretKey);

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed: authentication tag mismatch.');
        }

        return $plaintext;
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox((string) $value, $nonce, $this->secretKey);

        return sodium_bin2base64($nonce.$ciphertext, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }
}
