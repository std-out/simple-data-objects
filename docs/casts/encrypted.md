# EncryptedCast

Encrypts values when serializing to storage and decrypts them on hydration. Uses **XSalsa20-Poly1305 authenticated encryption** (via libsodium) — providing both confidentiality and integrity guarantees.

## Requirements

PHP's `sodium` extension (bundled with PHP 7.2+ by default).

## Usage

Attribute arguments only allow **constant expressions** — calling `env()` or `config()` inside an attribute is a compile error. Pass the *name* of an environment variable instead; the key is resolved from the environment when the cast is instantiated:

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;

class UserData extends BaseData
{
    public function __construct(
        public readonly string $name,

        #[Cast(new EncryptedCast(env: 'DATA_ENCRYPTION_KEY'))]
        public readonly string $taxId,

        #[Cast(new EncryptedCast(env: 'DATA_ENCRYPTION_KEY'))]
        public readonly ?string $creditCard = null,
    ) {}
}
```

A literal key is also accepted (`new EncryptedCast(key: '...')`) — useful in tests, but **avoid it in application code**: the key would live in your source tree. Exactly one of `key` / `env` must be provided; a missing or empty environment variable throws a `RuntimeException` immediately rather than silently encrypting with an empty key.

### Hydration (decrypt)

```php
$user = UserData::from([
    'name'  => 'Alice',
    'taxId' => 'v1:AAAA...base64...',  // ciphertext from database
]);

$user->taxId; // '1234567890' — plaintext
```

### Serialization (encrypt)

```php
$user->toArray()['taxId']; // 'v1:AAAA...base64...' — ciphertext, unique each call
```

Every `set()` call produces a **different ciphertext** because a random 24-byte nonce is generated for each encryption. This is normal and expected.

## Security Properties

| Property | Detail |
|---|---|
| Algorithm | XSalsa20-Poly1305 (`sodium_crypto_secretbox`) |
| Authentication | AEAD — tamper detection is built in |
| Key derivation | BLAKE2b (`sodium_crypto_generichash`) from the provided key string |
| Nonce | 24 random bytes prepended to each ciphertext |
| Encoding | Base64 URL-safe, no padding (SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING) |

## Key Handling

The cast is designed so key material never leaves process memory:

- **Never written to the metadata file cache.** `EncryptedCast` deliberately does not implement `__set_state()`, so classes using it are excluded from [file-based metadata persistence](../features/cache.md) — only the in-memory cache applies. Exporting the cast would write the key to disk in plaintext.
- **Not serializable.** `serialize()` on the cast throws a `LogicException`.
- **Redacted in debug output.** `var_dump()` / `print_r()` show `[redacted]` instead of the derived key, and the constructor parameter is marked `#[\SensitiveParameter]`, so the raw key does not appear in stack traces.

## Tamper Detection

If the ciphertext has been modified, `get()` throws `RuntimeException`:

```php
$user = UserData::from(['taxId' => 'v1:tampered_ciphertext']);
// throws RuntimeException: Failed to decrypt value: authentication tag mismatch
```

## Key Rotation

`EncryptedCast` uses the provided key directly. To rotate keys:

1. Deploy new cast instance with the new key
2. Re-encrypt existing records: read with old key, write with new key
3. Remove the old cast

## Breaking Change Notice

`EncryptedCast` uses XSalsa20-Poly1305 (libsodium). If you are upgrading from an older version that used AES-256-CBC (OpenSSL), existing ciphertexts are **not compatible** and must be re-encrypted.
