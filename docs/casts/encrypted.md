# EncryptedCast

Encrypts values when serializing to storage and decrypts them on hydration. Uses **XSalsa20-Poly1305 authenticated encryption** (libsodium `crypto_secretbox`), which provides confidentiality and detects tampering with a single ciphertext.

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

A literal key is also accepted (`new EncryptedCast(key: '...')`) — useful in tests, but **avoid it in application code**: the key would live in your source tree. Exactly one of `key` / `env` / `provider` must be provided; a missing or empty environment variable throws a `RuntimeException` immediately rather than silently encrypting with an empty key.

### Key provider (KMS, Vault, HSM)

To keep the key out of the process environment, pass a class implementing `EncryptionKeyProvider`. It is instantiated without arguments when the cast is built, and its `key()` is called once:

```php
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;

final class KmsKeyProvider implements EncryptionKeyProvider
{
    public function key(): string
    {
        return KmsClient::default()->decrypt(file_get_contents('/run/secrets/data-key.enc'));
    }
}

#[Cast(new EncryptedCast(provider: KmsKeyProvider::class))]
public readonly string $taxId,
```

A class that does not implement the interface throws `InvalidArgumentException`; an empty key throws `RuntimeException`.

### Hydration (decrypt)

```php
$user = UserData::from([
    'name'  => 'Alice',
    'taxId' => 'zW1o...base64url...',  // ciphertext from database
]);

$user->taxId; // '1234567890' — plaintext
```

### Serialization (encrypt)

```php
$user->toArray()['taxId']; // 'Qk3x...base64url...' — ciphertext, unique each call
```

Every `set()` call produces a **different ciphertext** because a random 24-byte nonce is generated for each encryption. This is normal and expected.

## Security Properties

| Property | Detail |
|---|---|
| Algorithm | XSalsa20-Poly1305 (`sodium_crypto_secretbox`) |
| Authentication | Poly1305 tag — a modified ciphertext is rejected. There is no associated data: a valid ciphertext copied to another field or record still decrypts |
| Key derivation | BLAKE2b (`sodium_crypto_generichash`) of the provided key string — a fast hash, not a password KDF |
| Nonce | 24 random bytes prepended to each ciphertext |
| Format | `base64url(nonce ‖ ciphertext)`, no padding, no version prefix or key ID |

## Key Handling

Use a key of **32 random bytes** (for example `base64_encode(random_bytes(32))`). Because the key is hashed with a fast function, a short or guessable string such as a password can be brute-forced from any stored ciphertext.

The cast keeps key material out of the places the library controls:

- **Never written to the metadata file cache.** `EncryptedCast` deliberately does not implement `__set_state()`, so classes using it are excluded from [file-based metadata persistence](../features/cache.md) — only the in-memory cache applies. Exporting the cast would write the key to disk in plaintext.
- **Not serializable.** `serialize()` on the cast throws a `LogicException`.
- **Hidden from every dump.** The derived key is held in a `\SensitiveParameterValue`, so `var_dump()`, `print_r()`, `var_export()`, `(array)` casts and `json_encode()` of the cast, or of metadata that contains it, never show it. The constructor parameter and the values passed to `get()` / `set()` are marked `#[\SensitiveParameter]`, so neither the key nor the plaintext appears in stack traces.

It cannot protect the key everywhere: a key read with `env:` stays in the process environment — use a [key provider](#key-provider-kms-vault-hsm) to avoid that.

## Tamper Detection

If the ciphertext has been modified, `get()` throws `RuntimeException`:

```php
$user = UserData::from(['taxId' => $tamperedCiphertext]);
// RuntimeException: Decryption failed: authentication tag mismatch.
```

Input that is not base64url or is shorter than a nonce throws `InvalidArgumentException` instead.

## Key Rotation

A ciphertext carries no key ID, so one cast instance can only read values written with its own key. Rotate with a one-off migration that holds both keys:

```php
$old = new EncryptedCast(env: 'DATA_ENCRYPTION_KEY_OLD');
$new = new EncryptedCast(env: 'DATA_ENCRYPTION_KEY');

foreach (Customer::query()->cursor() as $customer) {
    $customer->tax_id = $new->set($old->get($customer->tax_id));
    $customer->saveQuietly();
}
```

Until every row is migrated, the application can read only the rows written with the key it is configured with, so run the migration during a maintenance window or before switching the application to the new key.

## Breaking Change Notice

`EncryptedCast` uses XSalsa20-Poly1305 (libsodium). If you are upgrading from an older version that used AES-256-CBC (OpenSSL), existing ciphertexts are **not compatible** and must be re-encrypted.
