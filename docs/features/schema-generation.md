# Schema Generation — JSON Schema & TypeScript

`ClassMeta`/`ParameterMeta` already know everything about a class's shape (types, nullability, defaults, enum classes, nested DTOs, collections, mapped names) — `jsonSchema()` and the TypeScript generator turn that into a JSON Schema (draft 2020-12) or a `.d.ts` file, without a second reflection pass.

```php
OrderData::jsonSchema();
// [
//   'type' => 'object',
//   'properties' => [
//     'id' => ['type' => 'integer'],
//     'customerName' => ['type' => 'string'],
//     'shippingAddress' => ['$ref' => '#/$defs/AddressData'],
//   ],
//   'required' => ['id', 'customerName', 'shippingAddress'],
//   '$defs' => ['AddressData' => [...]],
// ]
```

## Type mapping

| PHP | Schema |
|---|---|
| `string` / `int` / `float` / `bool` / `array` | `string` / `integer` / `number` / `boolean` / `array` |
| nullable (`?T`) | `type` becomes `[T, 'null']` |
| enum | `enum: [...]` — case `->value` for backed enums, `->name` for pure enums |
| nested `BaseData` | `{$ref: '#/$defs/ClassName'}`, expanded once into `$defs` |
| `#[DataCollection]` | `{type: 'array', items: {$ref: ...}}` |
| `#[Flatten]` | the flattened class's own properties merge directly in, no `$ref` |
| `#[Hidden]` | omitted — the schema describes `toArray()`/`toJson()` output |
| `Optional` ([see Optional](./optional.md)) | excluded from `required`, not treated as nullable |
| `#[Discriminator]` | `{oneOf: [$ref, $ref, ...]}` over every mapped class (plus `fallback`, if set) |

`$defs` keys are the class's short name (`AddressData`, not the FQCN) — scanned
classes need unique short names for this to stay collision-free.

## Casts

A cast can implement `ProvidesJsonSchema` to control its own field's schema:

```php
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

final class PhoneCast implements CastsValue, ProvidesJsonSchema
{
    public function jsonSchema(): array
    {
        return ['type' => 'string', 'pattern' => '^\+?[0-9]{7,15}$'];
    }
}
```

Every built-in cast implements it: `DateTimeCast`/`DateTimeImmutableCast` emit
`format: date-time` when using the default `DateTimeInterface::ATOM` format (a
custom format only gets `type: string`, since an arbitrary date pattern has no
JSON Schema `format` keyword); `UuidCast` emits `format: uuid`; `MoneyCast` emits
`type: integer` (it serializes to bare minor-units, not an object — the currency is
fixed per field, not repeated on the wire); `CommaSeparatedCast` emits `type: string`
(its *serialization* direction re-joins the array back into a string); `JsonCast`
emits `{}` (genuinely open-shaped). A cast without `ProvidesJsonSchema` falls back to
the property's plain PHP type.

## `#[Rules]` / `#[InferRules]` — best-effort

A small, deliberately non-exhaustive subset of Laravel validation rules maps to
JSON Schema keywords; everything else is silently skipped:

| Rule | Schema keyword |
|---|---|
| `email` | `format: email` |
| `url` | `format: uri` |
| `uuid` | `format: uuid` |
| `in:a,b,c` | `enum: [a, b, c]` |
| `max:N` / `min:N` / `size:N` | `maxLength`/`minLength` (string), `maximum`/`minimum` (number), or `maxItems`/`minItems` (array) — picked from the field's own resolved type |

## TypeScript

```php
use StdOut\SimpleDataObjects\Support\CacheWarmer;
use StdOut\SimpleDataObjects\Support\TypeScriptGenerator;

$classes = CacheWarmer::discover(['app/Data']);
file_put_contents('resources/js/types/data-objects.d.ts', TypeScriptGenerator::generate($classes));
```

Or via the CLI / artisan command below. One `export interface` per concrete
`BaseData` class, one `export type X = A | B;` union per `#[Discriminator]` parent:

```ts
export interface OrderData {
  id: number;
  customerName: string;
  shippingAddress: AddressData;
  items: ItemData[];
  status: 'pending' | 'shipped' | 'cancelled';
  note?: string; // Optional — key may be entirely absent
}

export type PaymentMethodData = CardPaymentData | BankPaymentData;
```

Reuses the exact same per-field resolution `jsonSchema()` does — the one difference
is optionality: a JSON Schema's `required` list follows *hydration* semantics (is
this field required as *input*?), while a TS field's `?:` follows [`Optional`](./optional.md)
specifically, since that's the only thing ever conditionally missing from
`toArray()`/`toJson()` *output*. A plain nullable field (no `Optional`) always stays
a required TS key typed `T | null` — the key itself is never omitted, only its value
can be `null`.

Computed fields (`#[Computed]`) have no captured return type, so they're typed
`unknown` — a documented limitation, not a guess.

### CLI

```sh
vendor/bin/sdo-typescript resources/js/types/data-objects.d.ts app/Data
```

Without `<src-path>` arguments, the PSR-4 directories from `./composer.json` are
scanned.

### Artisan

```sh
php artisan sdo:typescript                                    # uses config
php artisan sdo:typescript app/Data --output=resources/js/types/data-objects.d.ts
```

See the `sdo:typescript` section in [Service Provider & Commands](../laravel/service-provider.md).
