# Changelog

All notable changes to `std-out/simple-data-objects` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
