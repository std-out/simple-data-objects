# Migrating from spatie/laravel-data

This page maps [`spatie/laravel-data`](https://github.com/spatie/laravel-data) concepts and attributes onto Simple Data Objects, for teams moving an existing DTO layer over.

::: warning Not a drop-in replacement
The two libraries make different trade-offs. A few `laravel-data` features don't have an equivalent here yet — see [What doesn't map over](#what-doesnt-map-over) before you start. Treat this as a reference for a manual rewrite, not a find-and-replace.
:::

## Core API

<div class="migrate-list">

<div class="migrate-item">

`class UserData extends Data` <span class="migrate-arrow">→</span> `class UserData extends BaseData`

</div>
<div class="migrate-item">

`UserData::from($payload)` <span class="migrate-arrow">→</span> `UserData::from($payload)`

Ours takes a single `$data` argument, not variadic `...$payloads` — merge sources yourself before calling.

</div>
<div class="migrate-item">

`UserData::collect($items)` <span class="migrate-arrow">→</span> `UserData::collection($items)`

Returns a `TypedDataCollection`, not a plain array.

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

`UserData::validate($payload)` <span class="migrate-arrow">→</span> `UserData::validate($payload)`

Throws `ValidationException`, works standalone (no Laravel app needed).

</div>
<div class="migrate-item">

*(no equivalent)* <span class="migrate-arrow">→</span> `UserData::fromResult($payload)`

Accumulates every field error instead of throwing on the first one — see [Error Accumulation](../features/error-accumulation.md).

</div>
<div class="migrate-item">

`$request->validated()` then `UserData::from(...)` <span class="migrate-arrow">→</span> `UserData::fromRequest($request)` or `UserData::fromValidated($request->all())`

`fromRequest()` lives in the opt-in `HasLaravelIntegration` trait, not core `BaseData`.

</div>
<div class="migrate-item">

`$data->wrap('data')` (instance call) <span class="migrate-arrow">→</span> `#[WrapIn('data')]` (class attribute)

Ours is fixed at the class level; `laravel-data`'s is set per call — there's no per-call override here.

</div>

</div>

## Attributes

<div class="migrate-list">

<div class="migrate-item">

`#[WithCast(SomeCast::class, ...$args)]` <span class="migrate-arrow">→</span> [`#[Cast(new SomeCast(...$args))]`](../attributes/cast.md)

Ours takes a constructed instance, not a class-string + arguments — see [Casts](#casts) below.

</div>
<div class="migrate-item">

`#[MapInputName('input_key')]` <span class="migrate-arrow">→</span> [`#[MapInputName('input_key')]`](../attributes/map-input-output-name.md) or [`#[MapPropertyName(...)]`](../attributes/map-property-name.md)

Same idea, same name. `MapPropertyName` accepts multiple aliases for the same property.

</div>
<div class="migrate-item">

`#[MapOutputName('output_key')]` <span class="migrate-arrow">→</span> [`#[MapOutputName('output_key')]`](../attributes/map-input-output-name.md)

Same.

</div>
<div class="migrate-item">

`#[MapName(SnakeCaseMapper::class)]` (class-level) <span class="migrate-arrow">→</span> [`#[TransformKeys(TransformKeys::SNAKE_CASE)]`](../attributes/transform-keys.md) (class-level)

Ours ships fixed strategies (snake/camel/studly/kebab) rather than a pluggable mapper class.

</div>
<div class="migrate-item" id="hidden">

`#[Hidden]` (property, unconditional) <span class="migrate-arrow">→</span> [`#[Hidden(except: ['admin'])]`](../attributes/hidden.md) (property)

Ours can be conditionally revealed per `toArray(context: 'admin')` call — `laravel-data`'s `Hidden` has no context system.

</div>
<div class="migrate-item">

`#[Computed]` (property) <span class="migrate-arrow">→</span> [`#[Computed]`](../attributes/computed.md) (method)

Different target: `laravel-data` marks a *property* as derived; ours marks a *method* whose return value becomes a serialized field. Expect to turn a computed property into a method.

</div>
<div class="migrate-item">

`#[DataCollectionOf(ItemData::class)]` <span class="migrate-arrow">→</span> [`#[DataCollection(ItemData::class)]`](../attributes/data-collection.md)

Same idea — typed collection of nested DTOs.

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

*(no equivalent)* <span class="migrate-arrow">→</span> [`#[Discriminator('type', [...])]`](../attributes/discriminator.md) (class)

Declarative polymorphic hydration by a discriminator field. `laravel-data` doesn't ship a direct equivalent — you'd wire this up yourself via a custom factory.

</div>

</div>

## Validation rules

`laravel-data` gives you one rule per attribute class (`#[Max(255)]`, `#[Email]`, `#[In(['a', 'b'])]`, ...) plus automatic inference from PHP types. Simple Data Objects takes the opposite approach: [`#[Rules([...])]`](../attributes/rules.md) takes a plain Laravel validation rule array — the same strings/objects you'd put in a `FormRequest` — and [`#[InferRules]`](../attributes/infer-rules.md) (class-level, opt-in) infers rules from property types the same way `laravel-data` does by default.

```php
// laravel-data
#[Max(200)]
#[Email]
public string $email;

// Simple Data Objects
#[Rules(['required', 'string', 'max:200', 'email'])]
public readonly string $email;
```

If you were relying on `laravel-data`'s per-property validation attributes, budget time to collapse each one into a Laravel rule string — there's no 1:1 attribute for attribute here.

## Casts

`laravel-data` casts implement `cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed` and are wired up via `#[WithCast(SomeCast::class, ...$args)]` — the attribute constructs the cast for you from a class-string.

Here, a cast implements `CastsValue` (`get(mixed $value): mixed` for hydration, `set(mixed $value): mixed` for serialization) and the attribute takes an already-constructed instance:

```php
// laravel-data
#[WithCast(DateTimeCast::class, 'Y-m-d')]
public DateTime $deliveryDate;

// Simple Data Objects
#[Cast(new DateTimeCast('Y-m-d'))]
public readonly DateTime $deliveryDate;
```

Built-in casts here: [`DateTimeCast`](../casts/date-time.md), `DateTimeImmutableCast`, [`EnumCast`](../casts/enum.md), [`BooleanCast`](../casts/boolean.md), [`IntegerCast` & `FloatCast`](../casts/numeric.md), [`TrimCast`](../casts/trim.md), [`JsonCast`](../casts/json.md), [`CommaSeparatedCast`](../casts/comma-separated.md), [`MoneyCast`](../casts/money.md), [`UuidCast`](../casts/uuid.md), [`EncryptedCast`](../casts/encrypted.md) (XSalsa20-Poly1305).

`laravel-data`'s `#[WithTransformer]` — a separate, output-only transformation step — has no distinct equivalent; a `CastsValue::set()` implementation covers the same ground on the serialization side.

## What doesn't map over {#what-doesnt-map-over}

A few `laravel-data` features are genuinely not available here yet. Don't look for a workaround — these need real feature work on our side:

- **`Lazy` properties** (`Lazy::create()`, `Lazy::when()`, `Lazy::whenLoaded()`) and the per-request `->only()` / `->except()` / `->include()` partial-payload API. `#[Hidden(except:)]` gives you static, context-based field visibility, but not `laravel-data`'s dynamic, caller-controlled partials.
- **Per-property validation rule attributes** (`#[Max]`, `#[Email]`, `#[In]`, and the rest of `Spatie\LaravelData\Attributes\Validation`) — use `#[Rules([...])]` with Laravel rule strings instead, as shown above.
- **A pluggable `NameMapper` for key transformation** — `#[TransformKeys]` covers the common cases (snake/camel/studly/kebab) but not a custom mapper class.

::: tip Missing something?
If any of these are the reason you haven't migrated, say so — open a [feature request](https://github.com/std-out/simple-data-objects/issues) or a [discussion](https://github.com/std-out/simple-data-objects/discussions). Concrete use cases are what decides what gets built next.
:::

## Migration checklist

1. Swap `extends Data` for `extends BaseData`.
2. Replace variadic `::from(...$payloads)` calls with a single merged array.
3. Rewrite `#[WithCast(X::class, ...$args)]` to `#[Cast(new X(...$args))]`.
4. Collapse per-property validation attributes into `#[Rules([...])]` arrays, or drop them in favor of class-level `#[InferRules]`.
5. Rename `#[DataCollectionOf]` to `#[DataCollection]`.
6. Check every `#[Computed]` property — it needs to become a method.
7. Check every `#[Hidden]` / wrap / `Lazy` usage against the gaps above before assuming it carries over unchanged.
8. Run your test suite. [`fromResult()`](../features/error-accumulation.md) is worth adopting here even where `laravel-data` code used `validate()` + `from()` separately — it collects every error in one pass.
