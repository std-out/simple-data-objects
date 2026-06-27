# #[Hidden]

Excludes a property from `toArray()`, `toJson()`, and `json_encode()` output. The property is still populated during hydration and accessible on the object.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\Hidden;

#[Hidden]
public readonly string $sensitiveField,
```

## Example

```php
class UserData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $passwordHash,
    ) {}
}

$user = UserData::from([
    'name'         => 'Alice',
    'email'        => 'alice@example.com',
    'passwordHash' => '$2y$12$...',
]);

$user->passwordHash;      // '$2y$12$...' — accessible on the object
$user->toArray();         // ['name' => 'Alice', 'email' => 'alice@example.com']
$user->toJson();          // '{"name":"Alice","email":"alice@example.com"}'
json_encode($user);       // same — passwordHash is absent
```

## Use Cases

- Password hashes, tokens, secrets
- Internal audit fields (created_by, updated_at) not meant for API clients
- Raw values before casting (when you expose only the cast version)
