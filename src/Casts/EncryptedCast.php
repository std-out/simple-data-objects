<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use SodiumException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;

/**
 * Authenticated encryption using XSalsa20-Poly1305 (libsodium).
 *
 * Deliberately NOT exportable to the metadata file cache (no __set_state):
 * exporting would write the key material to disk in plaintext. Classes using
 * this cast still get the in-memory metadata cache; only file persistence
 * is skipped.
 *
 * Breaking change from previous AES-256-CBC version: existing ciphertext
 * produced by the old cast is not compatible and must be re-encrypted.
 */
final class EncryptedCast implements CastsValue
{
    private readonly string $secretKey;

    /**
     * Attribute arguments only allow constant expressions — env() or config()
     * calls are a compile error there. Pass the NAME of an environment
     * variable via $env to keep the key itself out of source code:
     *
     *     #[Cast(new EncryptedCast(env: 'DATA_ENCRYPTION_KEY'))]
     */
    public function __construct(
        #[\SensitiveParameter] ?string $key = null,
        ?string $env = null,
    ) {
        if (($key === null) === ($env === null)) {
            throw new InvalidArgumentException('Provide exactly one of $key or $env.');
        }

        if ($env !== null) {
            $key = $_ENV[$env] ?? getenv($env);

            if (! is_string($key) || $key === '') {
                throw new RuntimeException("Environment variable '{$env}' is not set or empty.");
            }
        }

        // BLAKE2b: a secure, fast KDF — no brute-force shortcut unlike raw sha256
        $this->secretKey = sodium_crypto_generichash($key, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
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

    /** Prevents the derived key from leaking through serialize(). */
    public function __serialize(): array
    {
        throw new LogicException('EncryptedCast holds key material and must not be serialized.');
    }

    /** Redacts key material in var_dump()/print_r() output. */
    public function __debugInfo(): array
    {
        return ['secretKey' => '[redacted]'];
    }
}
