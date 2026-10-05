# Migrating from spatie/laravel-data

This page maps [`spatie/laravel-data`](https://github.com/spatie/laravel-data) concepts and attributes onto Simple Data Objects, for teams moving an existing DTO layer over. The `laravel-data` side was checked against v4.23.

::: warning Not a drop-in replacement
The two libraries make different trade-offs. A few `laravel-data` features don't have an equivalent here yet — see [What doesn't map over](#what-doesnt-map-over) before you start. Treat this as a reference for a manual rewrite, not a find-and-replace.
:::

::: tip Migrating with an AI agent
The repository ships a [`migrate-from-laravel-data` skill](https://github.com/std-out/simple-data-objects/blob/main/.claude/skills/migrate-from-laravel-data/SKILL.md) for Claude Code that follows this page class by class, runs your tests after each one, and stops to ask wherever a feature has no equivalent.
:::

## Before you start

- Simple Data Objects requires **PHP 8.4+** and **Laravel 12 or 13** (`illuminate/*` `^12.0|^13.0`).
- Install it alongside `laravel-data` and migrate one class at a time, running the tests that cover it. Remove `spatie/laravel-data` only when nothing references it any more.

## Core API

<div class="migrate-list">

<div class="migrate-item">

`class UserData extends Data` <span class="migrate-arrow">→</span> `class UserData extends BaseData`

`Resource` and `Dto` become `BaseData` too. Keep each property's mutability as it was — `readonly` is supported but not required.

</div>
<div class="migrate-item">

`UserData::from($payload)` <span class="migrate-arrow">→</span> `UserData::from($payload)`

Accepts an array, an `Arrayable` (models, collections), a JSON string, `stdClass`, or any object with public properties. **Never validates** — see [Validation rules](#validation-rules).

</div>
<div class="migrate-item">

`UserData::from($a, $b)` <span class="migrate-arrow">→</span> `UserData::from([...$a, ...$b])`

Ours takes a single `$data` argument, not variadic `...$payloads` — merge sources yourself before calling.

</div>
<div class="migrate-item">

`UserData::validateAndCreate($payload)` <span class="migrate-arrow">→</span> `UserData::fromValidated($payload)`

Validates `#[Rules]`, then hydrates; throws `ValidationException`.

</div>
<div class="migrate-item">

`UserData::validate($payload)` <span class="migrate-arrow">→</span> `UserData::validate($payload)`

Throws `ValidationException`, works standalone (no Laravel app needed). Returns `void`, where `laravel-data` returns the validated payload.

</div>
<div class="migrate-item">

`UserData::collect($items)` <span class="migrate-arrow">→</span> `UserData::collection($items)`

Always returns a `TypedDataCollection`; `laravel-data` returns the same kind of container it was given. Append `->all()` where an array is expected.

</div>
<div class="migrate-item">

`UserData::optional($payload)` <span class="migrate-arrow">→</span> `$payload === null ? null : UserData::from($payload)`

</div>
<div class="migrate-item">

`$data->toArray()` <span class="migrate-arrow">→</span> `$data->toArray()`

Ours accepts an optional `?string $context` for serialization groups — see [`#[Hidden]`](#hidden) below.

</div>
<div class="migrate-item">

`$data->toJson()` <span class="migrate-arrow">→</span> `$data->toJson()`

Ours accepts `int $flags = 0` and the same `?string $context`.

</div>
<div class="migrate-item">

`$data->with(...)` / `$data->additional([...])` <span class="migrate-arrow">→</span> [`#[Computed]`](../attributes/computed.md) method

**Name clash.** In `laravel-data` these append extra keys to the output; our [`with()`](../features/with.md) returns a modified *copy* of the object. A derived output field is a `#[Computed]` method here.

</div>
<div class="migrate-item">

`$data->only(...)` / `$data->except(...)` <span class="migrate-arrow">→</span> `$data->only(...)` / `$data->except(...)`

Same names, different result: ours return a plain **array**, not a chainable data object.

</div>
<div class="migrate-item">

`$data->wrap('data')` (instance call) <span class="migrate-arrow">→</span> [`#[WrapIn('data')]`](../attributes/wrap-in.md) (class attribute)

Ours is fixed at the class level; `laravel-data`'s is set per call — there's no per-call override here.

</div>
<div class="migrate-item">

*(no equivalent)* <span class="migrate-arrow">→</span> `UserData::fromResult($payload)`

Accumulates every field error instead of throwing on the first one — see [Error Accumulation](../features/error-accumulation.md).

</div>
<div class="migrate-item">

*(no equivalent)* <span class="migrate-arrow">→</span> `UserData::lazyXml($file, $path)`

Streams a large XML file into DTOs one element at a time — see [XML imports](#xml-imports).

</div>

</div>

## Attributes

<div class="migrate-list">

<div class="migrate-item">

`#[WithCast(SomeCast::class, ...$args)]` <span class="migrate-arrow">→</span> [`#[Cast(new SomeCast(...$args))]`](../attributes/cast.md)

Ours takes a constructed instance, not a class-string + arguments — see [Casts](#casts) below.

</div>
<div class="migrate-item">

`#[MapInputName('input_key')]` <span class="migrate-arrow">→</span> [`#[MapInputName('input_key')]`](../attributes/map-input-output-name.md)

Same name, same form — only the import changes.

</div>
<div class="migrate-item">

`#[MapOutputName('output_key')]` <span class="migrate-arrow">→</span> [`#[MapOutputName('output_key')]`](../attributes/map-input-output-name.md)

Same.

</div>
<div class="migrate-item">

`#[MapName('key')]` (property) <span class="migrate-arrow">→</span> [`#[MapPropertyName('key')]`](../attributes/map-property-name.md)

One key for both input and output. `MapPropertyName` also accepts several aliases for the same property.

</div>
<div class="migrate-item">

`#[MapName('in', 'out')]` (property) <span class="migrate-arrow">→</span> `#[MapInputName('in')]` + `#[MapOutputName('out')]`

Ours splits input and output mapping into two attributes.

</div>
<div class="migrate-item">

`#[MapName(SnakeCaseMapper::class)]` (class-level) <span class="migrate-arrow">→</span> [`#[TransformKeys(TransformKeys::SNAKE_CASE)]`](../attributes/transform-keys.md) (class-level)

Ours ships fixed strategies (snake/camel/studly/kebab) rather than a pluggable mapper class, and always renames keys in both directions — a class-level `MapInputName`/`MapOutputName` mapper for one direction only has no equivalent.

</div>
<div class="migrate-item" id="hidden">

`#[Hidden]` <span class="migrate-arrow">→</span> [`#[Hidden]`](../attributes/hidden.md)

Drop-in. Ours can additionally be revealed per call — `#[Hidden(except: ['admin'])]` with `toArray(context: 'admin')` — which `laravel-data`'s `Hidden` has no counterpart for.

</div>
<div class="migrate-item">

`#[Computed]` (property) <span class="migrate-arrow">→</span> [`#[Computed]`](../attributes/computed.md) (method)

Different target: `laravel-data` marks a *property* as derived; ours marks a *method* whose return value becomes a serialized field. Turn the computed property into a method, and every `$data->name` read into `$data->name()`.

</div>
<div class="migrate-item">

`#[DataCollectionOf(ItemData::class)]` <span class="migrate-arrow">→</span> [`#[DataCollection(ItemData::class)]`](../attributes/data-collection.md)

Type the property as `TypedDataCollection`. If it was typed `array`, its consumers now receive a collection.

</div>
<div class="migrate-item">

`Spatie\LaravelData\Optional` <span class="migrate-arrow">→</span> [`StdOut\SimpleDataObjects\Optional`](../features/optional.md)

Same idea (`string|Optional $name`). `Optional::create()` becomes `Optional::missing()`; an `Optional` property cannot also declare a default value.

</div>
<div class="migrate-item">

`#[WithoutValidation]` <span class="migrate-arrow">→</span> *(no attribute needed)*

Ours only validates properties that carry `#[Rules]` or fall under class-level `#[InferRules]` — omit the attribute instead of opting out.

</div>
<div class="migrate-item">

*(no equivalent)* <span class="migrate-arrow">→</span> [`#[Pipe(...)]`](../attributes/pipe.md)

Input-preprocessing middleware (value- or array-level), run before hydration. No `laravel-data` equivalent — closest is a custom cast, but a pipe runs before casting/validation, not instead of it.

</div>
<div class="migrate-item">

*(no equivalent)* <span class="migrate-arrow">→</span> [`#[Flatten]`](../attributes/flatten.md)

Inlines a nested DTO's fields into the parent's input/output. No `laravel-data` equivalent.

</div>
<div class="migrate-item">

*(no equivalent)* <span class="migrate-arrow">→</span> [`#[IgnoreIfNull]`](../attributes/ignore-if-null.md)

Omits a field from output entirely when `null`, instead of serializing it as `null`.

</div>
<div class="migrate-item">

`PropertyMorphableData` + `#[PropertyForMorph]` <span class="migrate-arrow">→</span> [`#[Discriminator('type', [...])]`](../attributes/discriminator.md) (abstract class)

Declarative polymorphic hydration by a discriminator field. `laravel-data` decides the concrete class in a `morph()` method; here that logic has to be expressed as a static value-to-class map.

</div>

</div>

## Validation rules {#validation-rules}

`laravel-data` gives you one rule per attribute class (`#[Max(255)]`, `#[Email]`, `#[In(['a', 'b'])]`, ...) plus automatic inference from PHP types. Simple Data Objects takes the opposite approach: [`#[Rules([...])]`](../attributes/rules.md) takes a plain Laravel validation rule array — the same strings/objects you'd put in a `FormRequest` — and [`#[InferRules]`](../attributes/infer-rules.md) (class-level, opt-in) infers rules from property types the same way `laravel-data` does by default.

```php
// laravel-data
#[Max(200)]
#[Email]
public string $email;

// Simple Data Objects
#[Rules(['required', 'string', 'max:200', 'email'])]
public string $email;

// or, under a class-level #[InferRules], only the extra rules
#[Rules(['max:200', 'email'], merge: true)]
public string $email;
```

Without `merge: true`, an explicit `#[Rules]` replaces the inferred rules for that property.

::: warning from() never validates
`laravel-data` validates automatically when a data object is created from a request. Here `from()` only hydrates. Every place that relied on that must switch to a validating entry point: `fromValidated()`, `fromRequest()`, or [controller injection](../laravel/service-provider.md#controller-injection).
:::

Rules defined by overriding `rules()`, `messages()` or `attributes()` on the data class have no equivalent — move them into `#[Rules]`, or keep a `FormRequest` for that endpoint.

## Casts

`laravel-data` casts implement `cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed` and are wired up via `#[WithCast(SomeCast::class, ...$args)]` — the attribute constructs the cast for you from a class-string.

Here, a cast implements `CastsValue` (`get(mixed $value): mixed` for hydration, `set(mixed $value): mixed` for serialization) and the attribute takes an already-constructed instance:

```php
// laravel-data
#[WithCast(DateTimeCast::class, 'Y-m-d')]
public DateTime $deliveryDate;

// Simple Data Objects
#[Cast(new DateTimeCast('Y-m-d'))]
public DateTime $deliveryDate;
```

When porting a custom cast:

- `get()` and `set()` receive only the value. A cast that reads the property definition, sibling values or the creation context has no direct equivalent.
- Add a static `__set_state()` so classes using the cast can be written to the [warmed metadata cache](../features/cache.md); without it they are silently left out of it. A cast holding secrets must *not* implement it.
- `#[WithTransformer]` — a separate, output-only step in `laravel-data` — becomes the cast's `set()`. Fold a property's cast and transformer into one `CastsValue`.
- There is no global or class-level cast registration: each property carries its own `#[Cast]`. Nested data objects and enums need none — they are resolved from the property type.

Built-in casts here: [`DateTimeCast`](../casts/date-time.md), `DateTimeImmutableCast`, [`EnumCast`](../casts/enum.md), [`BooleanCast`](../casts/boolean.md), [`IntegerCast` & `FloatCast`](../casts/numeric.md), [`TrimCast`](../casts/trim.md), [`JsonCast`](../casts/json.md), [`CommaSeparatedCast`](../casts/comma-separated.md), [`MoneyCast`](../casts/money.md), [`UuidCast`](../casts/uuid.md), [`EncryptedCast`](../casts/encrypted.md) (XSalsa20-Poly1305).

## Laravel integration

Everything Laravel-specific here is opt-in per class — nothing changes until the class asks for it.

<div class="migrate-list">

<div class="migrate-item">

Data class type-hinted as a controller parameter <span class="migrate-arrow">→</span> same, with [`HasLaravelIntegration`](../laravel/index.md) + the [service provider](../laravel/service-provider.md#controller-injection)

`laravel-data` does this out of the box. Here the class opts in with the trait, and the provider must be registered by hand in `bootstrap/providers.php` — it is deliberately not auto-discovered. Without it, call `UserData::fromRequest($request)`.

</div>
<div class="migrate-item">

`UserData::from($request)` <span class="migrate-arrow">→</span> `UserData::fromRequest($request)`

`fromRequest()` validates; plain `from($request)` would hydrate without validating.

</div>
<div class="migrate-item">

`UserData::from($model)` <span class="migrate-arrow">→</span> `UserData::from($model)` or `UserData::fromModel($model)`

`from()` uses `$model->toArray()`. `fromModel()` uses the model's own attributes plus only the relations marked [`#[WhenLoaded]`](../attributes/when-loaded.md).

</div>
<div class="migrate-item">

`'address' => AddressData::class` in model casts <span class="migrate-arrow">→</span> same, with `implements Castable` + `use IsEloquentCastable`

See [Eloquent Attribute Casting](../laravel/eloquent-casting.md).

</div>
<div class="migrate-item">

`DataCollection::class.':'.ItemData::class` in model casts <span class="migrate-arrow">→</span> `AsDataCollection::of(ItemData::class)`

</div>
<div class="migrate-item">

`Spatie\LaravelData\Concerns\WireableData` <span class="migrate-arrow">→</span> `StdOut\SimpleDataObjects\Concerns\WireableData`

Keep `implements Wireable` — see [Livewire Integration](../laravel/livewire.md).

</div>
<div class="migrate-item">

`UserData::collect($paginator)` <span class="migrate-arrow">→</span> `UserData::paginatedCollection($paginator)`

`LengthAwarePaginator` only — see [Pagination](../laravel/pagination.md). Cursor and simple paginators have no equivalent.

</div>

</div>

## XML imports {#xml-imports}

`laravel-data` has no XML support, so XML feeds are usually imported with a hand-written loop: SimpleXML or `XMLReader`, a function turning each element into an array, then `from()`. [`lazyXml()`](../features/xml.md) replaces the loop and the mapping function — the DTO describes the element, and the file is streamed one element at a time:

```php
// before
foreach (simplexml_load_file($file)->shop->offers->offer as $node) {
    $importer->process(OfferData::from(offerToArray($node)));
}

// after
OfferData::lazyXml($file, 'catalog/shop/offers/offer')
    ->each(fn (OfferData $offer) => $importer->process($offer));
```

This is an improvement you can make after the migration, not a required step. Before deleting the old mapping function, check what it did beyond copying values: only `int`/`float`/`bool` and int-backed enums are converted automatically, so dates, `yes`/`no` flags or decimal commas need a [`#[Cast]`](../attributes/cast.md); and malformed XML now throws `DataHydrationException`. The full rules are on the [Streaming XML](../features/xml.md) page.

## What doesn't map over {#what-doesnt-map-over}

A few `laravel-data` features are genuinely not available here yet. Don't look for a workaround — these need real feature work on our side:

- **`Lazy` properties** (`Lazy::create()`, `Lazy::when()`, `Lazy::whenLoaded()`, the `#[AutoLazy]` family) and the per-request `->only()` / `->except()` / `->include()` / `->exclude()` partial-payload API, including request-driven includes. `#[Hidden(except:)]` gives you static, context-based field visibility, but not `laravel-data`'s dynamic, caller-controlled partials.
- **Per-property validation rule attributes** (`#[Max]`, `#[Email]`, `#[In]`, and the rest of `Spatie\LaravelData\Attributes\Validation`) — use `#[Rules([...])]` with Laravel rule strings instead, as shown above.
- **A pluggable `NameMapper` for key transformation** — `#[TransformKeys]` covers snake/camel/studly/kebab, but not `LowerCaseMapper`, `UpperCaseMapper` or a custom mapper class.
- **Magic creation methods** — a `public static function fromSomething(...)` that `laravel-data`'s `from()` dispatches to by argument type. Our `from()` does not look for them; call the method by name.
- **Value-injecting attributes** — `#[FromRouteParameter]`, `#[FromAuthenticatedUser]`, `#[FromContainer]` and their variants, and `#[LoadRelation]`.
- **`$data->all()`** (properties without transformation), **`empty()`**, **`factory()`**, and custom `pipeline()` / `normalizers()` overrides. [`#[Pipe]`](../attributes/pipe.md) preprocesses input before hydration and may cover a custom pipeline, but the mapping isn't mechanical.

::: tip Missing something?
If any of these are the reason you haven't migrated, say so — open a [feature request](https://github.com/std-out/simple-data-objects/issues) or a [discussion](https://github.com/std-out/simple-data-objects/discussions). Concrete use cases are what decides what gets built next.
:::

## Migration checklist

1. Check PHP 8.4+ and Laravel 12/13, install the package alongside `laravel-data`, and make sure the test suite is green before touching anything.
2. Swap `extends Data` (or `Resource` / `Dto`) for `extends BaseData`.
3. Rewrite the attributes: `#[WithCast(X::class, ...$args)]` to `#[Cast(new X(...$args))]`, `#[DataCollectionOf]` to `#[DataCollection]`, `#[MapName]` to `#[MapPropertyName]` or `#[TransformKeys]`.
4. Port custom casts to `CastsValue`, with `__set_state()`.
5. Collapse per-property validation attributes into `#[Rules([...])]` arrays, or drop them in favor of class-level `#[InferRules]`.
6. Check every `#[Computed]` property — it needs to become a method.
7. Rewrite the call sites: variadic `::from(...)` to a single merged array, `collect()` to `collection()`, `validateAndCreate()` to `fromValidated()`, and every `->with()` / `->only()` / `->except()` against the differences above.
8. Find every place that relied on automatic request validation and give it a validating entry point.
9. Opt classes into the Laravel integration they use: controller injection, Eloquent casts, Livewire, pagination.
10. Check every `Lazy` / wrap / magic-`from` usage against [What doesn't map over](#what-doesnt-map-over) before assuming it carries over unchanged.
11. Run your test suite after each class. [`fromResult()`](../features/error-accumulation.md) is worth adopting here even where `laravel-data` code used `validate()` + `from()` separately — it collects every error in one pass.
12. When nothing references `Spatie\LaravelData` any more, `composer remove spatie/laravel-data`.
