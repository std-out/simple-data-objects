# #[MapInputName] / #[MapOutputName]

Maps hydration and serialization to different keys independently. Useful when migrating an API's field names: accept the old key on input while already emitting the new key on output.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\Attributes\MapOutputName;

#[MapInputName('legacy_id')]
#[MapOutputName('id')]
public readonly int $userId,
```

Both are independent — use either alone, or combine them on the same property. Neither can be combined with [`#[MapPropertyName]`](./map-property-name.md) on the same property; that attribute maps both directions to the same name at once.

## Example

```php
class UserData extends BaseData
{
    public function __construct(
        #[MapInputName('legacy_id')]
        #[MapOutputName('id')]
        public readonly int $userId,
    ) {}
}

$user = UserData::from(['legacy_id' => 42]);
$user->userId;       // 42
$user->toArray();     // ['id' => 42]
```

## `#[MapInputName]` accepts aliases too

Like `#[MapPropertyName]`, `#[MapInputName]` accepts multiple names — the first one present in the input wins:

```php
#[MapInputName('legacy_id', 'legacyId')]
public readonly int $userId,
```

## Roundtrip guarantee

`from($dto->toArray())` always works, even when the output key differs from every declared input name: the output key is automatically accepted as a fallback input alias (lowest priority, after any explicit `#[MapInputName]` aliases and the default PHP property name).

```php
#[MapOutputName('id')]           // no #[MapInputName] — input still reads 'userId'
public readonly int $userId,

UserData::from(['userId' => 42])->toArray();  // ['id' => 42]
UserData::from(['id' => 42]);                  // also accepted — makes the roundtrip work
```

## Using only one of the two

- `#[MapInputName]` alone leaves serialization on the default key (the PHP property name, or the [`#[TransformKeys]`](./transform-keys.md) strategy applied to it).
- `#[MapOutputName]` alone leaves hydration accepting the default key, in addition to the custom output key.
