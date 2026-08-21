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

## Context / groups

`except` makes a hidden field visible in specific output contexts — an admin-only
field, an internal note only your own services should see:

```php
class TicketData extends BaseData
{
    public function __construct(
        public readonly string $title,
        #[Hidden(except: ['admin'])]
        public readonly string $internalNote,
    ) {}
}

$ticket->toArray();                  // ['title' => '...'] — no internalNote
$ticket->toArray(context: 'admin');  // ['title' => '...', 'internalNote' => '...']
```

`toJson()` and `definedOnly()` accept the same `context` parameter. The context
propagates into nested `BaseData`, `#[DataCollection]`, and `#[Flatten]` fields, so a
nested object's own `#[Hidden(except:)]` rules apply consistently.

Each context compiles its own specialized serializer on first use (same
code-generation strategy as the default one — no runtime `if` per field) and is
cached separately; only the default context is written to the `.meta.php` warm cache.

**Limitations:** `only()`, `except()` (the instance methods), `jsonSerialize()`, and
therefore `json_encode($dto)`/implicit `JsonSerializable` usage always use the default
context — `jsonSerialize(): mixed` is a fixed-signature PHP interface method and can't
take a context argument. Call `toArray($context)`/`toJson($context)` directly when you
need a specific context.
