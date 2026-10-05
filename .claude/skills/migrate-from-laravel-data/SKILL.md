---
name: migrate-from-laravel-data
description: Migrate a PHP/Laravel codebase from spatie/laravel-data to std-out/simple-data-objects — finds every Data class, rewrites the class/attribute/cast/validation surface and the call sites to the SDO equivalent, flags what has no equivalent for human review, and verifies with the test suite. Also covers replacing hand-written XML-to-DTO import loops with lazyXml(). Use when the user asks to migrate, port, or replace spatie/laravel-data with simple-data-objects (or "SDO").
---

# Migrate from spatie/laravel-data to Simple Data Objects

This is a mechanical, file-by-file migration with a few genuinely unmappable
spots. Do not attempt a single repo-wide find-and-replace — go class by
class, verify as you go, and stop to ask the human at every point flagged
**STOP AND ASK** below rather than guessing.

The spatie side of every mapping below was checked against
spatie/laravel-data 4.23. If the project is on v3 or older, **STOP AND ASK**
— several names differ there (e.g. `::collection()` instead of `::collect()`).

## Before starting

1. Confirm `std-out/simple-data-objects` is installed alongside
   `spatie/laravel-data` (`composer require std-out/simple-data-objects`) —
   do not remove the spatie package until every class is migrated and the
   test suite is green again.
2. Confirm the project meets the requirements in its `composer.json`:
   PHP 8.4+ and `illuminate/*` 12 or 13 (Laravel 12/13). If either is lower,
   **STOP AND ASK** the human how they want to handle the upgrade before
   touching any code.
3. Run the existing test suite once and record whether it's green. If it
   isn't green before you start, **STOP AND ASK** — don't let pre-existing
   failures get blamed on the migration.
4. Work through one Data class per commit/step. After each class, run the
   tests that cover it before moving to the next.

## Step 1 — inventory every Data class

Search for all classes that need migrating. The import search is the
authoritative one — spatie classes also extend `Resource` or `Dto`, or use
the `WithData` trait, not only `Data`:

```
grep -rln "Spatie\\\\LaravelData" --include=*.php .
grep -rlnE "extends (Data|Resource|Dto)\b" --include=*.php .
```

For each match, open the file and read the full class before editing —
some may use features from "Not automatically portable" (Step 8) that
change the plan for that specific file. Files that only *use* a Data class
(controllers, jobs, models with casts) are call sites — they are handled in
Steps 6 and 7.

## Step 2 — class declaration and imports

```php
// before
use Spatie\LaravelData\Data;
class UserData extends Data { ... }

// after
use StdOut\SimpleDataObjects\BaseData;
class UserData extends BaseData { ... }
```

`Resource` and `Dto` become `BaseData` too.

Keep each property's mutability exactly as it was: do **not** add `readonly`
to properties that weren't readonly. spatie objects are often mutated after
creation (`$data->status = ...`), and SDO works with both.

Remove now-unused `use Spatie\LaravelData\...` imports as you replace what
they imported. Leave the import in place until every usage from it in the
file has been migrated, then remove it.

## Step 3 — attribute-by-attribute rewrite

| Find (spatie) | Replace with (SDO) | Notes |
|---|---|---|
| `#[WithCast(SomeCast::class, ...$args)]` | `#[Cast(new SomeCast(...$args))]` | Import `StdOut\SimpleDataObjects\Attributes\Cast`. The cast class itself also needs porting — see Step 4. |
| `#[MapInputName('key')]` (property) | `#[MapInputName('key')]` | Same name, same single-arg form — only the import changes. |
| `#[MapOutputName('key')]` (property) | `#[MapOutputName('key')]` | Same. |
| `#[MapName('key')]` (property, one argument) | `#[MapPropertyName('key')]` | One key for both input and output. |
| `#[MapName('in', 'out')]` (property) | `#[MapInputName('in')]` + `#[MapOutputName('out')]` | SDO splits input/output mapping into two attributes. |
| `#[MapName(SnakeCaseMapper::class)]` (class-level) | `#[TransformKeys(TransformKeys::SNAKE_CASE)]` | Also `CAMEL_CASE`, `STUDLY_CASE`, `KEBAB_CASE`. `#[TransformKeys]` renames keys in **both** directions, like `MapName`. |
| `#[MapInputName(SomeMapper::class)]` or `#[MapOutputName(SomeMapper::class)]` (class-level, one direction only) | **STOP AND ASK** | `#[TransformKeys]` cannot rename one direction only; the alternative is a per-property `#[MapInputName]` / `#[MapOutputName]` on every property. |
| `LowerCaseMapper`, `UpperCaseMapper`, or a custom `NameMapper` | **STOP AND ASK** | No pluggable-mapper equivalent. |
| `#[Hidden]` | `#[Hidden]` | Same name, drop-in. |
| `#[Computed]` on a **property** | `#[Computed]` on a **method** | Structural change, not a rename: move the computation into a public method with the same name as the property, returning the same value, and delete the property (and its assignment in the constructor). Every read of `$data->propertyName` anywhere in the project must become `$data->propertyName()`. |
| `#[DataCollectionOf(ItemData::class)]` | `#[DataCollection(ItemData::class)]` | Change the property type to `StdOut\SimpleDataObjects\TypedDataCollection` (an `Illuminate\Support\Collection` subclass). If the property was typed `array`, check every consumer — it now receives a collection. |
| `#[WithoutValidation]` | *(delete the attribute)* | SDO only validates properties carrying `#[Rules]` or under class-level `#[InferRules]`. |
| `Spatie\LaravelData\Optional` in a union type | `StdOut\SimpleDataObjects\Optional` | Same idea (`string\|Optional $name`). `$x instanceof Optional` keeps working; `Optional::create()` becomes `Optional::missing()`. An `Optional` property cannot also have a default value — remove the default. |

## Step 4 — casts and transformers

```php
// before
class SomeCast implements \Spatie\LaravelData\Casts\Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        return /* transform $value for hydration */;
    }
}

// after: two one-directional methods instead of one hydration-only method
class SomeCast implements \StdOut\SimpleDataObjects\Contracts\CastsValue
{
    public static function __set_state(array $state): self
    {
        return new self(/* constructor arguments, from $state */);
    }

    public function get(mixed $value): mixed
    {
        return /* transform $value for hydration — same logic as the old cast() body */;
    }

    public function set(mixed $value): mixed
    {
        return /* transform $value for serialization */;
    }
}
```

- `get()` and `set()` receive only the value. If the old `cast()` body reads
  `$property`, `$properties` (sibling values) or `$context`, **STOP AND ASK**
  — that information is not available to an SDO cast.
- Add `__set_state()`. Without it the class still works, but every Data
  class using the cast is silently left out of the warmed metadata file
  cache. Exception: a cast holding secrets (keys, tokens) must **not**
  implement it — that would write the secret to the cache file.
- `#[WithTransformer(SomeTransformer::class)]`: its `transform()` body
  becomes the cast's `set()`. If the property has both a cast and a
  transformer (or `#[WithCastAndTransformer]`), fold them into one
  `CastsValue`. If it has only a transformer, write a cast whose `get()`
  returns the value unchanged.
- `#[WithCastable(...)]`, global casts/transformers registered in
  `config/data.php`, and class-level `#[WithCast]`: **STOP AND ASK** — SDO
  has no global or class-level cast registration; each affected property
  needs its own `#[Cast]`.

Built-in casts already exist for common cases — prefer these over porting
a custom cast that duplicates one: `DateTimeCast`, `DateTimeImmutableCast`,
`EnumCast`, `BooleanCast`, `IntegerCast`, `FloatCast`, `TrimCast`,
`JsonCast`, `CommaSeparatedCast`, `MoneyCast`, `UuidCast`, `EncryptedCast`.
Nested data objects and enums need no cast at all — they are resolved from
the property type.

## Step 5 — validation rules

Spatie's per-property validation attributes (`#[Max(255)]`, `#[Email]`,
`#[In([...])]`, everything under `Spatie\LaravelData\Attributes\Validation`)
have no per-attribute equivalent. Collapse every validation attribute on a
property into one `#[Rules([...])]` array of plain Laravel rules:

```php
// before
#[Max(200)]
#[Email]
public string $email;

// after
#[Rules(['required', 'string', 'max:200', 'email'])]
public string $email;
```

- spatie adds type-based rules (`required`, `string`, …) on its own. Either
  write them out as above, or put `#[InferRules]` on the class and keep only
  the extra rules: `#[Rules(['max:200', 'email'], merge: true)]`. Without
  `merge: true`, an explicit `#[Rules]` replaces the inferred rules for that
  property.
- A class that overrides `rules()`, `messages()`, `attributes()`,
  `stopOnFirstFailure()` or `redirect()`: **STOP AND ASK** — there is no
  method-based rule definition in SDO.
- Prefer rule strings. Rule objects (`Rule::in(...)`, `new Enum(...)`) work,
  but a class using them is left out of the warmed metadata file cache.

**Behavior difference to carry over deliberately:** `from()` in SDO never
validates. spatie validates automatically when a Data object is created
from a request. Every such place must switch to a validating entry point —
see Step 6.

## Step 6 — call sites

Grep for every usage of each migrated class and rewrite the calls:

| Find (spatie) | Replace with (SDO) | Notes |
|---|---|---|
| `X::from($a)` | `X::from($a)` | Accepts an array, Arrayable (models, collections), JSON string, `stdClass`, or any object with public properties. |
| `X::from($a, $b, ...)` (several payloads) | `X::from([...$a, ...$b])` | SDO takes one argument — merge the sources first, later ones winning. |
| `X::validateAndCreate($payload)` | `X::fromValidated($payload)` | Validates `#[Rules]`, then hydrates; throws `ValidationException`. |
| `X::validate($payload)` | `X::validate($payload)` | Returns `void` in SDO (spatie returns the validated array) — if the return value is used, **STOP AND ASK**. |
| `X::collect($items)` | `X::collection($items)` | Always returns a `TypedDataCollection`; spatie returns the same kind of container it was given. If the result is used as an `array`, append `->all()`. A paginator argument is Step 7. |
| `X::collect($items, DataCollection::class)` / `new DataCollection(X::class, $items)` | `X::collection($items)` | |
| `X::optional($payload)` | `$payload === null ? null : X::from($payload)` | |
| `$data->toArray()`, `$data->toJson()` | same | |
| `$data->all()` | **STOP AND ASK** | spatie's `all()` returns properties without transforming them (nested objects stay objects); SDO has only `toArray()`. |
| `$data->with(...)` / `$data->additional([...])` | **STOP AND ASK** | **Name clash:** in spatie these append extra keys to the output; SDO's `with()` returns a modified *copy* of the object. A derived output field is a `#[Computed]` method. |
| `$data->only(...)` / `$data->except(...)` / `$data->include(...)` / `$data->exclude(...)` | **STOP AND ASK** | In SDO `only()`/`except()` return a plain **array**, not a chainable data object, and there are no includes — see Step 8. |
| `X::empty()`, `X::factory()`, `->transform(...)`, `X::from(...)` relying on a magic `fromSomething()` method | **STOP AND ASK** | See Step 8. |

## Step 7 — Laravel integration points

SDO's Laravel features are opt-in per class; nothing happens until the
class asks for it.

- **Controller/route parameters typed with a Data class.** spatie hydrates
  and validates these from the request automatically. In SDO this needs
  (a) `use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;` in the
  class and (b) `StdOut\SimpleDataObjects\Laravel\SimpleDataObjectsServiceProvider`
  registered manually in `bootstrap/providers.php` — it is not
  auto-discovered. Registering a provider is an app-wide change:
  **STOP AND ASK** once, before the first class that needs it. Without the
  provider, hydrate explicitly: `X::fromRequest($request)`.
- **`X::from($request)`** → `X::fromRequest($request)` (validates). Plain
  `from($request)` would hydrate without validating.
- **`X::from($model)`** → keeps working (uses `$model->toArray()`).
  `X::fromModel($model)` from `HasLaravelIntegration` uses the model's own
  attributes plus only the relations marked `#[WhenLoaded('relation')]`.
- **Returning a Data object from a controller** → `HasLaravelIntegration`
  provides `toResponse()`. A fixed `->wrap('data')` becomes a class-level
  `#[WrapIn('data')]`.
- **Eloquent casts.** `'address' => AddressData::class` in a model's casts
  keeps working once the class has `implements Illuminate\Contracts\Database\Eloquent\Castable`
  and `use StdOut\SimpleDataObjects\Concerns\IsEloquentCastable;`.
  `DataCollection::class.':'.ItemData::class` becomes
  `StdOut\SimpleDataObjects\Laravel\AsDataCollection::of(ItemData::class)`.
- **Livewire.** Swap `Spatie\LaravelData\Concerns\WireableData` for
  `StdOut\SimpleDataObjects\Concerns\WireableData`; keep `implements Wireable`.
- **Pagination.** `X::collect($paginator)` → `X::paginatedCollection($paginator)`
  (needs `HasLaravelIntegration`; `LengthAwarePaginator` only). Cursor or
  simple paginators: **STOP AND ASK**.

## Step 8 — not automatically portable: escalate, don't guess

If you find any of the following, **STOP AND ASK** the human how they want
to handle it for that specific class — do not invent a workaround:

- **`Lazy` properties** (`Lazy::create()`, `Lazy::when()`,
  `Lazy::whenLoaded()`, the `#[AutoLazy]` family) or call-site `->only()` /
  `->except()` / `->include()` / `->exclude()` partial payloads, including
  request-driven includes (`allowedRequestIncludes()` etc.). The nearest
  tool is `#[Hidden(except: ['context'])]` with `toArray(context: 'context')`,
  which is static per class definition, not dynamic per request — using it
  changes the class's public contract.
- **Magic creation methods** — a `public static function fromSomething(...)`
  on the Data class that spatie's `from()` dispatches to by argument type.
  SDO's `from()` does not look for them: every call site relying on one
  must call that method by name, and the method body must build the object
  itself (`new static(...)` or `static::from([...])`).
- **Property-morphable data** (`PropertyMorphableData`, `#[PropertyForMorph]`).
  SDO's `#[Discriminator('field', [...map])]` on an abstract class covers
  the common case, but the dispatch logic lives in a `morph()` method in
  spatie and must be re-expressed as a static map.
- **Value-injecting attributes** — `#[FromRouteParameter]`,
  `#[FromRouteParameterProperty]`, `#[FromAuthenticatedUser]`,
  `#[FromContainer]` and their variants, and `#[LoadRelation]`.
- **A custom pipeline or normalizer** — overriding `pipeline()`,
  `prepareForPipeline()` or `normalizers()`. SDO's `#[Pipe]` preprocesses
  input before hydration and may fit, but the mapping is not mechanical.
- **`$data->wrap('key')` with different keys at different call sites.**
  `#[WrapIn('key')]` is fixed at the class level.
- **`empty()`, `factory()`, TypeScript transformer attributes
  (`#[TypeScript]`), or anything else from `Spatie\LaravelData` that this
  document does not mention.**

## Step 9 — verify before moving to the next class

After each class:

1. Run static analysis / linting the project already uses (check
   `composer.json` scripts — commonly `phpstan`, `php-cs-fixer`, `pint`).
2. Run the tests that cover this Data class specifically, then the full
   suite.
3. Only after the full suite is green, move to the next class from the
   Step 1 inventory.

## Optional — XML imports

spatie/laravel-data has no XML support, so projects that import XML feeds
carry a hand-written loop: `simplexml_load_file()` or `XMLReader`, an
element-to-array mapping function, then `X::from($array)` / `X::collect()`.
SDO can replace the whole loop with `X::lazyXml($file, $path)`, which
streams the file one element at a time.

This is an improvement, not part of the migration: do it only if the human
asked for it, or list the candidates in the final summary. Find them with:

```
grep -rnE "simplexml_load_(file|string)|new XMLReader|XMLReader::(open|fromUri|XML)|SimpleXMLElement" --include=*.php .
```

To convert one import:

1. Make the DTO describe the element. **One element is one DTO**: a
   property reads the child element with its own name (text for a scalar
   property, the whole element for a nested data object), and a child that
   has attributes or children of its own gets its own data class. A
   property never reaches into a grandchild.
2. Add an attribute only where the default doesn't fit (all in
   `StdOut\SimpleDataObjects\Attributes`):

   | Attribute | The property reads |
   |---|---|
   | `#[XmlAttribute]` / `#[XmlAttribute('name')]` | an XML attribute of the element (named like the property unless given) |
   | `#[XmlElement('name')]` | the child element(s) with that name, when it differs from the property name |
   | `#[XmlText]` | the element's own text, on the DTO of an element that also has attributes |

   ```php
   // <offer id="42"><name>Kettle</name><price currency="UAH">499.90</price><picture>…</picture><picture>…</picture></offer>
   class OfferData extends BaseData
   {
       public function __construct(
           #[XmlAttribute]
           public int $id,
           public string $name,
           public PriceData $price,
           #[XmlElement('picture')]
           public array $pictures,
       ) {}
   }

   class PriceData extends BaseData
   {
       public function __construct(
           #[XmlText]
           public float $amount,
           #[XmlAttribute]
           public string $currency,
       ) {}
   }
   ```

3. Replace the loop:

   ```php
   // before
   foreach (simplexml_load_file($file)->shop->offers->offer as $node) {
       $importer->process(OfferData::from(offerToArray($node)));
   }

   // after — the second argument is the slash-separated element path from the root
   OfferData::lazyXml($file, 'catalog/shop/offers/offer')
       ->each(fn (OfferData $offer) => $importer->process($offer));
   ```

4. Check these behaviors against the old mapping function before deleting it:
   - `int`, `float`, `bool` properties and int-backed enums are converted
     from the declared type; `bool` is true only for `true` and `1`. Any
     other conversion the old function did (dates, `yes`/`no`, trimming,
     decimal commas) needs a `#[Cast]`.
   - An empty element or attribute becomes `null` for a nullable property.
   - An `array` property collects the text of every repeated element; a
     `#[DataCollection]` property hydrates every repeated element; with no
     matching element a required list is empty.
   - Elements the DTO doesn't declare are skipped. If the old function
     computed a field from several elements, or reached into grandchildren,
     that logic moves to nested DTOs or a `#[Computed]` method — if it
     doesn't fit, **STOP AND ASK** and leave the hand-written loop in place.
   - Unreadable, malformed or truncated XML throws `DataHydrationException`
     instead of whatever the old code did (warnings, `false`, a partial
     result). Check the caller's error handling.
   - The file must be reachable by a path or stream wrapper
     (`compress.zlib://feed.xml.gz` works). XML held in a string has no
     `lazyXml()` entry point — keep the existing code for that.
   - Requires `ext-xmlreader`.

5. Run the import's tests. If there are none, **STOP AND ASK** before
   replacing a working import with no way to verify it.

## When every class is migrated

1. Re-run the full test suite once more.
2. Grep for any remaining `Spatie\LaravelData` references
   (`grep -rln "Spatie\\\\LaravelData" --include=*.php .`) — there should be
   none left outside `vendor/`. Also check `config/data.php` and any
   published spatie stubs; remove them only once nothing references them.
3. Only then run `composer remove spatie/laravel-data`.
4. Report a summary to the human: how many classes were migrated
   automatically, a list of anything raised as **STOP AND ASK** that still
   needs a decision, and any XML imports found that could move to
   `lazyXml()`.
