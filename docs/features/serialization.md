# Serialization

Every `BaseData` instance can be serialized to array, JSON, or string.

## toArray()

Returns all non-hidden properties as an associative array. Casts are applied in the `set()` direction (e.g., `DateTime` → formatted string).

```php
$user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

$user->toArray();
// ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null]
```

## toJson() / __toString()

```php
$user->toJson();   // '{"name":"Alice","email":"alice@example.com","phone":null}'
(string) $user;    // same
json_encode($user); // works via JsonSerializable
```

## Partial Output

```php
$user->only('name', 'email'); // ['name' => 'Alice', 'email' => 'alice@example.com']
$user->except('phone');       // ['name' => 'Alice', 'email' => 'alice@example.com']
```

## Nested DTOs in Output

Nested DTO instances are recursively serialized:

```php
$profile->toArray();
// [
//   'user'    => ['name' => 'Alice', 'email' => 'alice@example.com'],
//   'address' => ['street' => '123 Main St', 'city' => 'Kyiv'],
// ]
```

## Hidden Fields

Use [`#[Hidden]`](../attributes/hidden.md) to exclude a property from all output:

```php
class AuthData extends BaseData
{
    public function __construct(
        public readonly string $username,
        #[Hidden]
        public readonly string $passwordHash,
    ) {}
}

AuthData::from([...])->toArray(); // ['username' => 'alice']
```

## Omitting Null Values

Use [`#[IgnoreIfNull]`](../attributes/ignore-if-null.md) to skip a property when its value is `null`:

```php
class ArticleData extends BaseData
{
    public function __construct(
        public readonly string  $title,
        #[IgnoreIfNull]
        public readonly ?string $subtitle = null,
    ) {}
}

ArticleData::from(['title' => 'Hello'])->toArray();
// ['title' => 'Hello']  — subtitle omitted
```

## Cast Serialization

Casts transform values in both directions. On `toArray()`, the `set()` method of the cast is called:

| Cast | Stored value | `toArray()` output |
|---|---|---|
| `DateTimeCast('Y-m-d')` | `DateTime` object | `'2025-06-15'` |
| `BooleanCast` | `true` | `true` |
| `JsonCast` | `['key' => 'val']` | `'{"key":"val"}'` |
| `EncryptedCast('key')` | plaintext string | AES ciphertext |

See [Casts →](../casts/index.md) for the full reference.
