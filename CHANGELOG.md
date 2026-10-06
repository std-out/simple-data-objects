# Changelog

All notable changes to `std-out/simple-data-objects` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **Documentation:** every figure on the Performance page now comes from the
  public benchmark project (booted Laravel app, medians of 7 runs), replacing
  the earlier standalone-script numbers. The "50× less memory with
  `lazyCollection()`" claim is gone: with both libraries streaming, peak
  memory is equal and the difference is throughput (~1.85×).
- **Documentation:** new navigation by section, home page and theme.
- **Documentation:** the XML figures in the README and on the
  [Performance](https://std-out.github.io/simple-data-objects/guide/performance)
  page now come from the public benchmark (`make bench-xml`), and memory is
  compared with SimpleXML twice — a loop that accumulates nothing (692 MB)
  and every row collected first (998 MB), against 55 MB for `lazyXml()`.

## [2.2.0] — 2026-10-05

### Added
- **`migrate-from-laravel-data` agent skill** (`.claude/skills/`) — a Claude
  Code skill that migrates a codebase from `spatie/laravel-data` class by
  class: attributes, casts, validation, call sites and Laravel integration
  points, running the tests after each class and stopping to ask wherever a
  feature has no equivalent. Also covers replacing hand-written XML import
  loops with `lazyXml()`.

### Changed
- **Documentation:** the
  [migration guide](https://std-out.github.io/simple-data-objects/guide/migrating-from-laravel-data)
  is rewritten to match the skill — call-site changes (`collect()`,
  `validateAndCreate()`, the `with()` name clash), the fact that `from()`
  never validates, a Laravel integration section, XML imports, and a longer
  list of what doesn't map over.
- **Documentation:** streaming XML benchmark figures added to the README.

No library code changed in this release.

## [2.1.0] — 2026-10-05

### Added
- **`lazyXml()`** — stream a large XML file into DTOs one element at a time:
  `OfferData::lazyXml($file, 'catalog/shop/offers/offer')` returns a
  `LazyCollection` that never holds more than the current element in memory.
  - One element, one DTO: a property reads the child element with its own
    name, and a child with structure of its own is a nested DTO.
  - **`#[XmlAttribute]`**, **`#[XmlElement('name')]`** and **`#[XmlText]`**
    point a property at an XML attribute, a differently named child element,
    or the element's own text. They only affect `lazyXml()` — array
    hydration and serialization of the same class are unchanged.
  - `int`/`float`/`bool` properties and int-backed enums are converted from
    the declared type; nested `BaseData`, `#[DataCollection]`, `#[Flatten]`,
    `#[Discriminator]` and `array` lists of repeated elements are supported.
  - Elements the DTO doesn't declare are skipped without being materialized.
  - Malformed or unreadable XML throws `DataHydrationException`.
  - Requires `ext-xmlreader` (bundled with PHP by default); nothing changes
    for code that doesn't call it.
- **Documentation:** see [Streaming XML](https://std-out.github.io/simple-data-objects/features/xml).

### Changed
- `ParameterMeta` gains an `xmlSource` field, defaulted in `__set_state()`,
  so `.meta.php` caches written by earlier versions keep loading.

## [2.0.1] — 2026-09-04

### Changed
- **Documentation only:** v2 became the default docs with v1 moved under
  `/v1/`, a migration guide from `spatie/laravel-data` was added, and the
  README's use cases and benchmark context were clarified. No library code
  changed.

## [2.0.0] — 2026-08-09

### Added
- **`Optional`** — a sentinel distinguishing "key absent from input" from an
  explicit `null`, for PATCH-style partial updates. Added as a union member
  on the property type: `public readonly string|Optional $name`.
  - A missing key resolves to `Optional::missing()` instead of a default,
    `null`, or a thrown error. `Optional::isMissing($value)` checks it.
  - **Nullable-optional** (`string|Optional|null $bio`): a present `null`
    still clears the field; only an absent key resolves to missing.
  - **Serialization**: a still-missing field is always omitted from
    `toArray()` and everything built on it (`toJson()`, `only()`,
    `except()`, `diff()`, `equals()`) — unconditional, not opt-in like
    `#[IgnoreIfNull]`. Keeps `from($dto->toArray())` roundtrip-safe.
  - **`definedOnly()`** — `toArray()` under a name that reads clearly at
    PATCH call sites (`$model->update($data->definedOnly())`).
  - **`with()`** copies a missing field through unchanged, and can
    explicitly reset one back to missing via `with(field: Optional::missing())`.
  - **`#[InferRules]`** infers a `sometimes` presence rule instead of
    `required`/`nullable` for an `Optional` field (`sometimes` +
    `nullable` for the nullable-optional case), so PATCH endpoints don't
    end up requiring every field.
  - Works on nested `BaseData` and `#[DataCollection]` fields, and on both
    `from()`/`tryFrom()` and `fromResult()`/`fromValidatedResult()`.
  - **`#[WhenLoaded]` synergy**: an unloaded Eloquent relation is already a
    genuinely absent key, so it resolves to `Optional::missing()` with no
    extra configuration.
  - Cannot be combined with a declared default value or with `#[Flatten]`
    — both throw at metadata-build time.
- **Documentation:** see [Optional — Absent vs Null](https://std-out.github.io/simple-data-objects/features/optional).
- **`#[Hidden(except: [...])]`** — serialization groups/context. A field can now
  be hidden by default and shown only in specific output contexts, instead of
  the previous all-or-nothing behavior; bare `#[Hidden]` is unchanged.
  - **`toArray(context: 'admin')`** (and `toJson()`, `definedOnly()`) picks
    which context to render. Each context compiles its own specialized
    serializer, cached separately — zero runtime branching per field, same
    strategy as the default serializer.
  - Propagates into nested `BaseData`, `#[DataCollection]`, and `#[Flatten]`
    fields automatically.
  - `HasLaravelIntegration::toResponse()` and `PaginatedDataCollection`
    accept the same `context` parameter.
  - Only the default context is written to the `.meta.php` warm cache; other
    contexts compile lazily on first use.
  - `only()`, `except()`, `jsonSerialize()` (and therefore `json_encode($dto)`)
    stay context-free — the former to avoid a breaking variadic-signature
    change, the latter because `JsonSerializable` is a fixed PHP interface.
- **Documentation:** see [#[Hidden]](https://std-out.github.io/simple-data-objects/attributes/hidden).
- **`jsonSchema()` + TypeScript generation** — describe a class's shape (JSON
  Schema draft 2020-12, or a `.d.ts` file) from the same `ClassMeta`/`ParameterMeta`
  hydration/serialization already walk — no extra reflection.
  - `OrderData::jsonSchema(): array` — scalars, nullable (`type: [T, 'null']`),
    enums (`enum: [...]`, backed values or pure-case names), nested `BaseData`
    (`$ref`/`$defs`), `#[DataCollection]` (`array` + `items`), `#[Flatten]`
    (merged inline), `#[Hidden]` (omitted), `Optional` (excluded from
    `required`, not treated as nullable), `#[Discriminator]` (`oneOf`,
    including the `fallback` class).
  - **`ProvidesJsonSchema`** — optional cast interface; every built-in cast
    implements it (`DateTimeCast`/`DateTimeImmutableCast`, `UuidCast`,
    `MoneyCast`, `CommaSeparatedCast`, `EncryptedCast`, `JsonCast`, and the
    plain scalar casts).
  - **`#[Rules]`/`#[InferRules]` best-effort mapping**: `email`, `url`,
    `uuid`, `in:`, `max:`/`min:`/`size:` (context-sensitive: `maxLength` vs
    `maximum` vs `maxItems`) — everything else is silently skipped.
  - **`TypeScriptGenerator`** — one `export interface` per concrete class,
    one `export type X = A | B;` union per `#[Discriminator]` parent. Built
    directly on the JSON Schema per-field resolution; the one divergence is
    optionality — a TS `?:` follows `Optional` specifically (the only thing
    ever missing from `toArray()`/`toJson()` output), not the JSON Schema
    `required` list (which describes hydration input requirements instead).
  - **CLI `bin/sdo-typescript`** and **artisan `sdo:typescript`** — same
    discovery (`CacheWarmer::discover()`) and config
    (`simple-data-objects.paths`) as `sdo:warm`; new
    `simple-data-objects.typescript_output` config key.
- **Documentation:** see [Schema Generation](https://std-out.github.io/simple-data-objects/features/schema-generation).

### Changed
- **Breaking (internal API only):** `Support\TypeResolver::resolve()` now
  returns a 3-element tuple (`[$nestedDataClass, $enumClass, $isOptional]`)
  instead of 2. This is `@internal` support code with a single call site
  (`ClassMetaFactory`), not part of `BaseData`'s public hydration contract
  — no change for consumers of `from()`/`toArray()`/etc.
- **Breaking (internal API only):** `Support\SerializerCompiler` gained
  `$contextualSerializers` (`array<class-string, array<string, Closure>>`,
  non-default contexts only) alongside the existing `$serializers`, which
  keeps its original single-level shape — the default (no-context) path pays
  no extra array-dimension cost. `@internal`, `BaseData::toArray()` already
  updated — no change for consumers.

## [1.23.0] — 2026-08-08

### Added
- **`fromResult()` / `fromValidatedResult()`** — a "safe parse" alternative
  to `from()`/`tryFrom()` that never throws. Every parameter is tried, and
  every failure is collected into a `HydrationResult` instead of aborting
  on the first one.
  - **`HydrationResult`** — `ok()`, `value()` (throws `LogicException` if
    called on a failed result), `valueOrNull()`, `errors()`.
  - **Dot-path errors for nested structure**: a nested `BaseData` or
    `#[DataCollection]` field recurses into the target class's own
    `fromResult()`, merging its errors under `field.name` /
    `field.index.name`. `#[Flatten]` merges flat, with no prefix, since its
    fields already live in the parent's own namespace.
  - `#[RejectUnknownKeys]` reports a `'$unknown'` entry instead of
    aborting; a failing class-level `#[Pipe]` reports `'$pipeline'` (and
    skips per-field extraction, since the transform left the input
    unreliable); a throwing constructor reports `'$construct'`; invalid
    non-array input reports `'$input'`.
  - **`fromValidatedResult()`** merges `#[Rules]`/`#[InferRules]` failures
    into the same error map (the first message per field; a validation
    message wins over a hydration message on the same key).
  - Compiled the same way `from()` is — a specialized closure per class,
    cached separately and lazily — so `from()`/`tryFrom()` pay nothing for
    it, whether or not a class ever calls `fromResult()`.
- **Documentation:** see [fromResult() — Error Accumulation](https://std-out.github.io/simple-data-objects/features/error-accumulation).

## [1.22.0] — 2026-08-05

### Added
- **`#[Computed]`** — adds a derived, method-backed field to serialization
  output. Works on constructor-based, constructor-less, and hybrid classes.
  - Public, non-static, zero-required-parameter methods only; a method
    requiring parameters throws at metadata-build time.
  - Default output key is the method name (subject to the class-level
    `#[TransformKeys]` strategy, same as properties); override with
    `#[Computed('custom_key')]`.
  - The return value is normalized the same way any other field is —
    nested `BaseData`, enums, and `Collection`s serialize correctly.
  - Ignored on input, so `from($dto->toArray())` keeps working;
    `#[RejectUnknownKeys]` treats the computed key as known.
- **Documentation:** see [#[Computed]](https://std-out.github.io/simple-data-objects/attributes/computed).

## [1.21.0] — 2026-08-04

### Added
- **`#[MapPropertyName]` now accepts aliases** — `#[MapPropertyName('user_id', 'userId', 'uid')]`
  tries each input key in order, first one present wins. Serialization still
  uses the first alias, unchanged from before.
- **`#[MapInputName]` / `#[MapOutputName]`** — map hydration and
  serialization keys independently, for APIs migrating a field name over
  time (accept the old key on input, already emit the new key on output).
  `#[MapInputName]` also accepts aliases. Cannot be combined with
  `#[MapPropertyName]` on the same property.
  - **Roundtrip invariant preserved:** whenever the output key diverges from
    the declared input names, it's automatically accepted as a fallback
    input alias too, so `from($dto->toArray())` keeps working.
  - `#[RejectUnknownKeys]` treats every alias and the output key as known.
- **Documentation:** see [#[MapPropertyName]](https://std-out.github.io/simple-data-objects/attributes/map-property-name) and [#[MapInputName] / #[MapOutputName]](https://std-out.github.io/simple-data-objects/attributes/map-input-output-name).

## [1.20.0] — 2026-08-02

### Added
- **`PaginatedDataCollection`** — wraps a Laravel paginator into a
  `{"data":[...],"meta":{...},"links":{...}}` envelope, hydrating items
  through the same compiled hydrator as `from()`.
  - `HasLaravelIntegration::paginatedCollection()` — opt-in entry point,
    e.g. `ItemData::paginatedCollection(Item::paginate(20))`.
  - `Responsable` + `JsonSerializable` — return it directly from a
    controller.
  - No new hard dependency: only `Illuminate\Contracts\Pagination\*`
    (already required via `illuminate/contracts`); `illuminate/pagination`
    is require-dev, needed only by the concrete paginator you pass in.
- **`#[WrapIn]`** — wraps a single object's `toResponse()` payload under a
  key (`{"data": {...}}`), without touching `toArray()`/`toJson()` — the
  `from(toArray())` round-trip stays intact.
- **`toResponse()`** now accepts `status` and `headers` parameters.
- **Documentation:** see [Pagination & Response Envelope](https://std-out.github.io/simple-data-objects/laravel/pagination) and [#[WrapIn]](https://std-out.github.io/simple-data-objects/attributes/wrap-in).

## [1.19.0] — 2026-08-02

### Added
- **`#[InferRules]`** — auto-generate validation rules from property types.
  - Opt-in per class: `string`/`int`/`float`/`bool`/`array` map to their
    matching Laravel rule, enums to `Rule::enum(...)`, all prefixed with
    `required` or `nullable` based on the property's own nullability.
  - **Nested cascade:** a nested `BaseData` or `#[DataCollection]` property
    gets `['required'|'nullable', 'array']` for itself, plus the nested
    class's own rules cascaded under dot notation (`address.city`,
    `items.*.price`). Only cascades through classes that themselves
    contribute rules; self-referential and mutually cyclic `BaseData`
    graphs stop at the cycle instead of recursing forever.
  - **`#[Rules]` interop:** an explicit `#[Rules]` on a property still
    replaces the inferred rule by default — pass `merge: true` to append
    to it instead (`#[Rules(['max:100'], merge: true)]`).
  - Zero runtime cost: resolved once in `ClassMetaFactory::build()` and
    cached like every other rule, same as hand-written `#[Rules]`.
  - **Documentation:** see [#[InferRules]](https://std-out.github.io/simple-data-objects/attributes/infer-rules).

## [1.18.0] — 2026-07-30

### Removed
- **Support for Laravel 10 and 11.** Both majors are past their security-support
  window: every `laravel/framework` release on either line — including the
  newest patch — is now flagged by Packagist's security-advisory database, so
  Composer 2.9+ refuses to install them at all (`config.policy.advisories.block`
  rejects the resolution outright). This isn't a version bump we chose;
  dependency resolution for those majors is no longer possible. Minimum
  supported range is now Laravel 12–13. `illuminate/contracts`,
  `illuminate/support`, `illuminate/validation`, `illuminate/console`,
  `illuminate/database`, and `illuminate/http` all move to `^12.0|^13.0`.

## [1.17.0] — 2026-07-29

### Added
- **`SimpleDataObjectsServiceProvider`** — artisan commands and automatic controller injection.
  - **Manual Registration:** Not auto-discovered — register it yourself in `bootstrap/providers.php`. Every other Laravel-facing piece in this package is already opt-in per-class, and this is the one that adds process-wide container behavior, so turning it on is a deliberate step rather than something that changes behavior for every Laravel app that installs this package.
  - **Automatic Injection & Validation:** Type-hint a `BaseData` subclass that uses `HasLaravelIntegration` as a controller or route-closure parameter and it hydrates + validates from the current request automatically.
    - No `FormRequest` needed.
    - A validation failure still surfaces as the normal `ValidationException` → `422`.
  - **Zero Overhead:** Implemented as a `beforeResolving(BaseData::class, ...)` container hook scoped to `BaseData` and its subclasses, so it adds no overhead to unrelated container resolutions. Classes without `HasLaravelIntegration`, already explicitly bound classes, and abstract base classes are all left alone.
  - **Global Configuration:** Opt out globally with `inject_from_request => false` in the new publishable `config/simple-data-objects.php`.
  - **New Artisan Commands:**
    - `sdo:warm` / `sdo:clear`: Thin wrappers over the existing `CacheWarmer`/`MetadataRegistry`, auto-registered against `php artisan optimize`.
    - `make:data`: A DTO stub generator.
      - `--from-model`: Reads a model's table columns (`Schema::getColumns()`, Laravel 11+) into typed constructor-promoted properties.
      - `--rules`: Adds inferred `#[Rules]`.
      - `--collection`: Adds a doc-comment pointing at the existing `static::collection()`.
  - **Documentation:** See [Service Provider & Commands](https://std-out.github.io/simple-data-objects/laravel/service-provider).

## [1.16.0] — 2026-07-27

### Added
- **`IsEloquentCastable` trait and `AsDataCollection` — Eloquent attribute
  casting.** Assign a data object directly as an Eloquent attribute cast;
  hydration and serialization go through the same `from()`/`toArray()`
  round trip as everywhere else. `AsDataCollection::of(ItemData::class)`
  does the same for a JSON array column. Both casters support Eloquent's
  dirty-check via `compare()`, comparing decoded values instead of raw
  JSON bytes — Laravel 12+ only; on 10.x/11.x, dirty-checking falls back
  to Eloquent's default byte comparison. Works with an abstract
  `#[Discriminator]` class as the cast target. Fully decoupled, like
  `HasLaravelIntegration` and `WireableData`: no dependency on
  `illuminate/database`. See
  [Eloquent Attribute Casting](https://std-out.github.io/simple-data-objects/laravel/eloquent-casting).
- **`#[WhenLoaded]`.** `fromModel()` now hydrates from `$model->attributesToArray()`
  (no relations) and adds a relation only when its property is marked
  `#[WhenLoaded('relationName')]` and the relation is actually loaded;
  otherwise the property falls back to its default like any missing field.
  Works with `#[DataCollection]` for `hasMany`/`belongsToMany` relations. See
  [`#[WhenLoaded]`](https://std-out.github.io/simple-data-objects/attributes/when-loaded).
- **`#[RejectUnknownKeys]` — strict mode.** Hydration throws
  `DataHydrationException` when the input contains a key the class doesn't
  recognize, instead of silently ignoring it — checked against each
  parameter's input name (after `#[MapPropertyName]`/`#[TransformKeys]`),
  before pipes or per-field extraction run. The exception's `$unknownKeys`
  array exposes the offending keys directly. Rejected at metadata-build
  time when combined with `#[Flatten]` or `#[Discriminator]`, since neither
  has a fixed set of keys to check against. See
  [`#[RejectUnknownKeys]`](https://std-out.github.io/simple-data-objects/attributes/reject-unknown-keys).

## [1.15.0] — 2026-07-26

### Added
- **`WireableData` trait — Livewire integration.** Adds `toLivewire()` /
  `fromLivewire()`, delegating to the existing `toArray()`/`from()` round
  trip so casts (enums, `DateTimeCast`, custom casts, ...) apply the same
  way they do everywhere else. Fully decoupled like `HasLaravelIntegration`:
  the package has no dependency on `livewire/livewire`, and the trait does
  not `implements \Livewire\Wireable` itself — that interface is untyped,
  so the trait's methods already satisfy it structurally. The consuming
  class adds `implements \Livewire\Wireable` itself, which is the only
  place `livewire/livewire` needs to be installed. See
  [Livewire Integration](https://std-out.github.io/simple-data-objects/laravel/livewire).

## [1.14.0] — 2026-07-25

### Added
- **`#[Discriminator]` — polymorphic hydration.** An abstract data class can
  now map a discriminator field's value to concrete subclasses:
  `PaymentMethodData::from(['type' => 'card', ...])` returns a
  `CardPaymentData`. Resolution happens inside the compiled hydrator (one
  array lookup — no reflection at runtime) and works across every entry
  point: `from()`, `tryFrom()`, `fromJson()`, `fromLazy()` (the concrete
  class is resolved eagerly, hydration stays deferred), `fromValidated()`
  and `validate()` (which delegate so the concrete subclass's `#[Rules]`
  apply), `collection()`, `lazyCollection()`, nested properties typed as
  the abstract base, and `#[DataCollection]` of the base. Supports string
  and integer map keys, `BackedEnum` input values, an optional `fallback`
  class for missing/unmapped values, and multi-level hierarchies (a map
  target may itself be a `#[Discriminator]` class). All configuration
  errors — non-abstract class, empty map, unknown or foreign target
  classes — are caught at metadata-build time, and the compiled dispatcher
  persists through the metadata file cache like any other hydrator.

## [1.13.0] — 2026-07-24

### Fixed
- `BaseData` subclasses declared **without a constructor** (plain typed
  property declarations, e.g. `public ?string $name = null;`) previously
  hydrated to a default-initialized instance with the entire input array
  silently discarded, and `toArray()` always returned `[]`. Both now work
  correctly, including `readonly` properties, `fromLazy()`, and `with()`.

### Added
- **Constructor-less and hybrid DTOs.** A `BaseData` subclass no longer needs
  a constructor — plain typed property declarations are hydrated via
  post-construction assignment instead. Classes may also mix both styles: a
  constructor with promoted properties plus additional plain properties
  declared in the class body are hydrated together, in one call. Both styles
  support the full attribute set (`#[Cast]`, `#[DataCollection]`,
  `#[Flatten]`, `#[Hidden]`, `#[IgnoreIfNull]`, `#[MapPropertyName]`,
  `#[Pipe]`, `#[Rules]`) and readonly properties. Only public, non-static,
  typed properties are considered — static, private/protected, and untyped
  properties are ignored, same as they always were for constructor
  parameters. Pure constructor-only classes (the common case) compile to
  byte-identical code — zero behavior or performance change.

## [1.12.0] — 2026-07-22

### Added
- `MoneyCast` and `ValueObjects\Money` — a small immutable money value
  object (minor units + currency) instead of floats. Accepts int minor
  units, a decimal string, an `['amount' => ..., 'currency' => ...]` array,
  or an existing `Money` on hydration; serializes back to int minor units.
  Currency is fixed per field via the cast constructor and validated on
  both directions; raw `float` input is rejected. Decimal-string parsing
  is done without float arithmetic, so equally-precise half-cent amounts
  round consistently (e.g. `"1.005"` and `"2.005"` no longer drift apart
  depending on binary float representation).

## [1.11.0] — 2026-07-15

### Added
- `CommaSeparatedCast` — splits a delimited string into an array on
  hydration and joins it back on serialization (`separator` and `trim`
  are configurable; default `,` and `true`).

## [1.10.0] — 2026-07-15

### Added
- `UuidCast` — validates RFC 4122 UUID strings on hydration and normalizes
  to lowercase on both hydration and serialization. Invalid input throws
  `InvalidArgumentException`.

## [1.9.0] — 2026-07-15

### Added
- `LowercaseValuePipe` and `UppercaseValuePipe` — case normalization for
  `#[Pipe]`-attributed properties (e.g. emails, currency/country codes).
  Non-string values pass through untouched, matching `TrimValuePipe`'s contract.

### Changed
- Documentation site: custom VitePress theme with breadcrumb navigation.

## [1.8.0] — 2026-07-03

### Changed
- Slimmer Composer installs: dev-only files (tests, docs, CI config, Docker setup)
  are now excluded from dist archives via `.gitattributes` export-ignore.

## [1.7.2] — 2026-07-03

### Added
- **Universal `from()`** — a single factory that accepts arrays (unchanged fast
  path), Eloquent models and any `Arrayable`, `stdClass`, `JsonSerializable`,
  any `Traversable`, JSON strings, plain objects with public properties, and
  same-class instances (returned as-is). All detection lives on the cold path —
  the hot array path executes the same opcodes as before.
- **`BaseData::fromLazy()`** — lazy hydration built on native PHP 8.4 lazy
  ghosts; hydration runs on first property access. With ~10% of objects
  actually read: ~3× faster on cast-heavy DTOs, ~6× with nested collections.
- Integrations documentation: Plain PHP, Laravel, Symfony, Slim/PSR-7, plus an
  `opcache.preload` recipe.

### Changed
- `fromJson()` is now an explicit alias of `from()`.
- Compiled hydrator is hoisted out of collection loops
  (`TypedDataCollection::of()`, `lazyCollection()`): +19% on collection
  hydration (220k → 267k ops/s).

### Fixed
- `HydratorCompiler::compile()` now fails fast when given a non-`BaseData`
  class (e.g. via `TypedDataCollection::of()`).

## [1.4.3] — 2026-07-02

### Added
- **Compiled hot path** — `from()` and `toArray()` now execute a specialized
  closure generated per data class: plain properties become inline array
  reads. Steady-state throughput: hydration ~2.6×, serialization ~2.2× over
  the previous interpreted path. Behavior is unchanged.
- **`vendor/bin/sdo-warm`** + `Support\CacheWarmer` — pre-build the metadata
  cache on deploy; scans PSR-4 dirs from `composer.json` for concrete
  `BaseData` subclasses, fails fast on invalid DTO definitions.
- **`BaseData::lazyCollection()`** — stream large iterables with a flat memory
  profile (~0.26 MB peak for 50k rows vs ~13 MB materialized).

### Changed
- Cache format v2: `.meta.php` files now carry the compiled hydrator and
  serializer alongside the metadata — a warmed FPM worker pays neither
  reflection nor `eval` (opcache serves the whole file). Legacy v1 cache files
  still load.
- `ParameterMeta::$isPlain` is precomputed so hot paths skip `ValueCaster`
  entirely for plain properties.

### Removed
- The interpreted `Hydrator` (fully replaced by compiled hydrators).

## [1.1.15] — 2026-07-02

### Added
- Tests for `EncryptedCast` and expanded enum-handling coverage.

### Changed
- Improved enum handling and metadata caching.
- CI: dynamic coverage badge, updated GitHub Actions.

## [1.0.0] — 2026-07-01

First stable release. **100% test coverage**, enforced in CI ever since.

### Added
- **`#[Pipe]`** — middleware-style input preprocessing at class or property
  level, with built-in pipes: `TrimStringsPipe`, `NullifyEmptyStringsPipe`,
  `TrimValuePipe`, `NullifyEmptyStringValuePipe`.
- **`#[TransformKeys]`** — class-level key transformation (snake, studly,
  kebab strategies) via `KeyTransformer`.
- **File-based metadata cache** — `MetadataRegistry::setStoragePath()` with
  atomic writes, `__set_state`-based serialization, and an exportability guard.
- New API surface: `tryFrom()`, `only()`, `except()`, `with()`, `diff()`,
  `equals()`, `fromJson()`, `fromValidated()`, `TypedDataCollection::last()`.
- `bin/check-coverage.php` — CI gate that fails below 100% coverage.

## [0.6.7] — 2026-06-27

### Added
- Exportable metadata via `__set_state` (groundwork for the file cache).

### Changed
- Hardened `EncryptedCast` (XSalsa20-Poly1305 via libsodium).

## [0.6.3] — 2026-06-27

### Added
- **Validation** via the `#[Rules]` attribute — works inside Laravel and
  standalone, no app container required.
- Data manipulation methods and first pass of metadata caching.

## [0.3.0] — 2026-06-26

### Added
- Advanced casting and `#[IgnoreIfNull]` — omit `null` fields from output.

## [0.2.1] — 2026-06-26

### Added
- **`#[Cast]`** attribute and the value-casting engine with the first set of
  built-in casts.

## [0.1.0] — 2026-06-26

Initial release.

### Added
- `BaseData` — attribute-driven DTOs for PHP 8.4+: hydration via `from()`,
  serialization via `toArray()` / `toJson()`.
- Typed collections, Laravel integration (`fromRequest()`, `fromModel()`,
  `toResponse()`).
- CI pipeline: tests across Laravel 10–13 and a scheduled `composer audit`.

[Unreleased]: https://github.com/std-out/simple-data-objects/compare/v1.8.0...HEAD
[1.8.0]: https://github.com/std-out/simple-data-objects/compare/v1.7.2...v1.8.0
[1.7.2]: https://github.com/std-out/simple-data-objects/compare/v1.4.3...v1.7.2
[1.4.3]: https://github.com/std-out/simple-data-objects/compare/v1.1.15...v1.4.3
[1.1.15]: https://github.com/std-out/simple-data-objects/compare/v1.0.0...v1.1.15
[1.0.0]: https://github.com/std-out/simple-data-objects/compare/v0.6.7...v1.0.0
[0.6.7]: https://github.com/std-out/simple-data-objects/compare/v0.6.3...v0.6.7
[0.6.3]: https://github.com/std-out/simple-data-objects/compare/v0.3.0...v0.6.3
[0.3.0]: https://github.com/std-out/simple-data-objects/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/std-out/simple-data-objects/compare/v0.1.0...v0.2.1
[0.1.0]: https://github.com/std-out/simple-data-objects/releases/tag/v0.1.0
