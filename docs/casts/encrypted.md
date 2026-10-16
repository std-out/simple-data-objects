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

### Keyring format (recommended)

Pass a class implementing `EncryptionKeyring` to switch the cast to the keyring format. It is opt-in: without `keyring:` the cast behaves exactly as before.

```php
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyring;

final class AppKeyring implements EncryptionKeyring
{
    public function currentKeyId(): string
    {
        return '2026';
    }

    public function keys(): array
    {
        return [
            '2025' => getenv('DATA_KEY_2025'),
            '2026' => getenv('DATA_KEY_2026'),
        ];
    }
}

#[Cast(new EncryptedCast(keyring: AppKeyring::class))]
public readonly string $pan,
```

What changes compared to the legacy format:

- **Key ID in every value.** Values look like `v2:2026:…`; new values use `currentKeyId()`, older IDs stay readable, so keys rotate without downtime.
- **Bound to the field.** The class and property name are authenticated as associated data. A ciphertext copied into another field (`pan` → `holder`) or another class fails with `RuntimeException: Decryption failed`. Subclasses share the binding of the class that declares the property. Pass `context: 'customers.pan'` to choose the binding yourself, for example to keep data readable after renaming a class.
- **Length hidden.** The plaintext is padded to 32-byte blocks, so a 13-digit and a 19-digit card number produce ciphertexts of the same length.
- **Strong keys only.** Each key must be 32 random bytes — raw, or base64 / base64url-encoded (`base64_encode(random_bytes(32))`). Passwords and other printable strings are rejected. A per-purpose subkey is derived with `sodium_crypto_kdf_derive_from_key`.
- **Key ID format.** IDs match `[A-Za-z0-9_-]{1,32}`.

To migrate existing data, keep the old key next to the keyring. The cast then reads both formats and writes only the new one:

```php
#[Cast(new EncryptedCast(env: 'DATA_ENCRYPTION_KEY', keyring: AppKeyring::class))]
```

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

| Property | Keyring format | Legacy format |
|---|---|---|
| Algorithm | XChaCha20-Poly1305-IETF (`sodium_crypto_aead_xchacha20poly1305_ietf_*`) | XSalsa20-Poly1305 (`sodium_crypto_secretbox`) |
| Authentication | Poly1305 tag over the ciphertext, the key ID and the class + field binding | Poly1305 tag over the ciphertext only: a valid ciphertext copied to another field or record still decrypts |
| Key | 32 random bytes per key ID; subkey via `sodium_crypto_kdf_derive_from_key` | Any string, hashed with BLAKE2b — a fast hash, not a password KDF |
| Length | Padded to 32-byte blocks (`sodium_pad`) | Ciphertext length reveals plaintext length |
| Nonce | 24 random bytes | 24 random bytes |
| Format | `v2:<key id>:base64url(nonce ‖ ciphertext)` | `base64url(nonce ‖ ciphertext)` |

Both formats are told apart by the `v2:` prefix, which cannot occur in base64url.

## Key Handling

Use a key of **32 random bytes** (for example `base64_encode(random_bytes(32))`). The keyring format enforces this. The legacy format hashes any string with a fast function, so a short or guessable key such as a password can be brute-forced from any stored ciphertext.

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

With a keyring, add the new key, point `currentKeyId()` at it and deploy: new writes use it, old values stay readable. Re-encrypt old rows at your own pace and remove the old key only when nothing uses it:

```php
$cast = (new EncryptedCast(keyring: AppKeyring::class))->forField(CustomerData::class, 'pan');

foreach (Customer::query()->cursor() as $customer) {
    if ($cast->needsReencryption($customer->pan)) {
        $customer->pan = $cast->set($cast->get($customer->pan));
        $customer->saveQuietly();
    }
}
```

`forField()` applies the same binding the DTO uses. Skip it if the cast has an explicit `context:`. `needsReencryption()` is `true` for legacy values and for values written with an older key ID. When the cast also has a legacy key, the same loop migrates legacy data to the keyring format.

Without a keyring, a ciphertext carries no key ID, so one cast instance can only read values written with its own key. Rotate with a one-off migration that holds both keys:

```php
$old = new EncryptedCast(env: 'DATA_ENCRYPTION_KEY_OLD');
$new = new EncryptedCast(env: 'DATA_ENCRYPTION_KEY');

foreach (Customer::query()->cursor() as $customer) {
    $customer->tax_id = $new->set($old->get($customer->tax_id));
    $customer->saveQuietly();
}
```

Until every row is migrated, the application can read only the rows written with the key it is configured with, so run the migration during a maintenance window or before switching the application to the new key.

## Searching encrypted fields

Ciphertexts are random, so `WHERE pan = ?` cannot match them. Store a blind index next to the encrypted column and query by it:

```php
use StdOut\SimpleDataObjects\Support\BlindIndex;

$index = new BlindIndex(env: 'PAN_INDEX_KEY', context: 'customers.pan');

$customer->pan_index = $index->hash($pan);
Customer::where('pan_index', $index->hash($request->pan))->first();
$index->matches($pan, $customer->pan_index);
```

`hash()` is a keyed HMAC-SHA256, base64url-encoded (43 characters). Use a separate key — 32 random bytes, raw or base64 — that is not one of the encryption keys. `context` separates indexes of different columns. An index reveals which rows share a value, so index only what you search by, and normalize input (strip spaces and dashes) before hashing. `BlindIndex` cannot be serialized and does not show its key in dumps.

## Breaking Change Notice

`EncryptedCast` uses XSalsa20-Poly1305 (libsodium). If you are upgrading from an older version that used AES-256-CBC (OpenSSL), existing ciphertexts are **not compatible** and must be re-encrypted.
