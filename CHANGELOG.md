# Changelog

All notable changes to `std-out/simple-data-objects` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
