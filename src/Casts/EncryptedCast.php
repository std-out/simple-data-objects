<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use SodiumException;
use StdOut\SimpleDataObjects\Contracts\BindsToField;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyring;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;
use StdOut\SimpleDataObjects\Support\KeyMaterial;
use StdOut\SimpleDataObjects\Support\Redactor;

/**
 * Authenticated encryption using libsodium.
 *
 * Deliberately NOT exportable to the metadata file cache (no __set_state):
 * exporting would write the key material to disk in plaintext. Classes using
 * this cast still get the in-memory metadata cache; only file persistence
 * is skipped.
 */
final class EncryptedCast implements BindsToField, CastsValue, ProvidesJsonSchema
{
    private const string PREFIX = 'v2:';

    private const int PAD_BLOCK = 32;

    private const string KDF_CONTEXT = 'SDOcrypt';

    private const string KEY_ID_PATTERN = '/^[A-Za-z0-9_-]{1,32}$/';

    private readonly ?\SensitiveParameterValue $secretKey;

    private readonly ?\SensitiveParameterValue $keyring;

    private readonly ?string $currentKeyId;

    private readonly bool $hasExplicitContext;

    private string $associatedData;

    /**
     * Attribute arguments only allow constant expressions — env() or config()
     * calls are a compile error there. Pass the NAME of an environment
     * variable via $env, or an EncryptionKeyProvider class via $provider, to
     * keep the key itself out of source code:
     *
     *     #[Cast(new EncryptedCast(env: 'DATA_ENCRYPTION_KEY'))]
     *     #[Cast(new EncryptedCast(provider: KmsKeyProvider::class))]
     *     #[Cast(new EncryptedCast(keyring: AppKeyring::class))]
     *
     * @param  class-string<EncryptionKeyProvider>|null  $provider
     * @param  class-string<EncryptionKeyring>|null  $keyring
     */
    public function __construct(
        #[\SensitiveParameter] ?string $key = null,
        ?string $env = null,
        ?string $provider = null,
        ?string $keyring = null,
        ?string $context = null,
    ) {
        $legacyKey = KeyMaterial::resolve($key, $env, $provider);

        if ($legacyKey === null && $keyring === null) {
            throw new InvalidArgumentException('Provide $keyring, or exactly one of $key, $env or $provider.');
        }

        $this->secretKey = $legacyKey === null
            ? null
            : new \SensitiveParameterValue(sodium_crypto_generichash($legacyKey, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        if ($keyring === null) {
            $this->keyring = null;
            $this->currentKeyId = null;
        } else {
            [$keys, $this->currentKeyId] = self::loadKeyring($keyring);
            $this->keyring = new \SensitiveParameterValue($keys);
        }

        $this->hasExplicitContext = $context !== null;
        $this->associatedData = $context ?? '';
    }

    public function forField(string $class, string $field): static
    {
        if ($this->hasExplicitContext) {
            return $this;
        }

        $bound = clone $this;
        $bound->associatedData = $class.'::'.$field;

        return $bound;
    }

    public function get(#[\SensitiveParameter] mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (str_starts_with((string) $value, self::PREFIX)) {
            return $this->open((string) $value);
        }

        if ($this->secretKey === null) {
            throw new RuntimeException('Encrypted value uses the legacy format, but no $key, $env or $provider is configured to read it.');
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

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->secretKey->getValue());

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed: authentication tag mismatch.');
        }

        return $plaintext;
    }

    public function set(#[\SensitiveParameter] mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            throw new InvalidArgumentException('EncryptedCast can only encrypt scalar or Stringable values, got '.get_debug_type($value).'.');
        }

        if ($this->keyring !== null) {
            return $this->seal((string) $value);
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox((string) $value, $nonce, $this->secretKey->getValue());

        return sodium_bin2base64($nonce.$ciphertext, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    public function needsReencryption(string $value): bool
    {
        return $this->keyring !== null && ! str_starts_with($value, self::PREFIX.$this->currentKeyId.':');
    }

    /** Prevents the derived key from leaking through serialize(). */
    public function __serialize(): array
    {
        throw new LogicException('EncryptedCast holds key material and must not be serialized.');
    }

    /** Redacts key material in var_dump()/print_r() output. */
    public function __debugInfo(): array
    {
        return ['secretKey' => Redactor::REDACTED];
    }

    public function jsonSchema(): array
    {
        return ['type' => 'string'];
    }

    private function seal(#[\SensitiveParameter] string $plaintext): string
    {
        $keyId = (string) $this->currentKeyId;
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            sodium_pad($plaintext, self::PAD_BLOCK),
            $this->aad($keyId),
            $nonce,
            $this->keyring->getValue()[$keyId],
        );

        return self::PREFIX.$keyId.':'.sodium_bin2base64($nonce.$ciphertext, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    private function open(#[\SensitiveParameter] string $value): string
    {
        if ($this->keyring === null) {
            throw new RuntimeException('Encrypted value uses the keyring format, but no $keyring is configured to read it.');
        }

        $parts = explode(':', $value, 3);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Encrypted value is malformed.');
        }

        [, $keyId, $payload] = $parts;
        $key = $this->keyring->getValue()[$keyId]
            ?? throw new RuntimeException("Unknown encryption key id '".Redactor::forMessage($keyId)."'.");

        try {
            $decoded = sodium_base642bin($payload, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        } catch (SodiumException) {
            throw new InvalidArgumentException('Encrypted value is not valid base64.');
        }

        if (strlen($decoded) <= SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new InvalidArgumentException('Encrypted value is too short.');
        }

        $padded = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($decoded, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES),
            $this->aad($keyId),
            substr($decoded, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES),
            $key,
        );

        if ($padded === false) {
            throw new RuntimeException('Decryption failed: authentication tag mismatch.');
        }

        return sodium_unpad($padded, self::PAD_BLOCK);
    }

    private function aad(string $keyId): string
    {
        return self::PREFIX.$keyId.':'.$this->associatedData;
    }

    /**
     * @param  class-string<EncryptionKeyring>  $keyring
     * @return array{array<string, string>, string}
     */
    private static function loadKeyring(string $keyring): array
    {
        if (! is_subclass_of($keyring, EncryptionKeyring::class)) {
            throw new InvalidArgumentException("Keyring '{$keyring}' must implement ".EncryptionKeyring::class.'.');
        }

        $source = new $keyring;
        $keys = [];

        foreach ($source->keys() as $keyId => $material) {
            $keyId = (string) $keyId;

            if (preg_match(self::KEY_ID_PATTERN, $keyId) !== 1) {
                throw new InvalidArgumentException("Key id '".Redactor::forMessage($keyId)."' of {$keyring} must match [A-Za-z0-9_-]{1,32}.");
            }

            $keys[$keyId] = sodium_crypto_kdf_derive_from_key(
                KeyMaterial::BYTES,
                1,
                self::KDF_CONTEXT,
                KeyMaterial::strict($material, "Key '{$keyId}' of {$keyring}"),
            );
        }

        $current = $source->currentKeyId();

        if (! isset($keys[$current])) {
            throw new InvalidArgumentException("Current key id '".Redactor::forMessage($current)."' is not among the keys of {$keyring}.");
        }

        return [$keys, $current];
    }
}
