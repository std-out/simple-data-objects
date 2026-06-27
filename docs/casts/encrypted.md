# EncryptedCast

Encrypts values when serializing to storage and decrypts them on hydration. Uses **XSalsa20-Poly1305 authenticated encryption** (via libsodium) — providing both confidentiality and integrity guarantees.

## Requirements

PHP's `sodium` extension (bundled with PHP 7.2+ by default).

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;

class UserData extends BaseData
{
    public function __construct(
        public readonly string $name,

        #[Cast(new EncryptedCast(key: env('SECRET_KEY')))]
        public readonly string $taxId,

        #[Cast(new EncryptedCast(key: env('SECRET_KEY')))]
        public readonly ?string $creditCard = null,
    ) {}
}
```

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
