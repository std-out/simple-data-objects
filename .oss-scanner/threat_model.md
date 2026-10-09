# Threat model

## What this project does and where untrusted input enters

Simple Data Objects is a PHP 8.4+ library that turns raw input into typed, immutable data objects (DTOs) and back. It has
no network surface of its own: it is called by application code, typically with data that came from an HTTP request, a
queue message, a database column or an imported file.

Treat as **attacker-controlled**:

- The value passed to `BaseData::from()`, `tryFrom()`, `fromResult()`, `fromValidated()`, `fromValidatedResult()`,
  `fromLazy()`, `validate()`, `collection()` and `lazyCollection()` — arrays, JSON strings, `stdClass`, and anything
  nested inside them, including values that reach casts (`src/Casts/*`), pipes (`src/Pipes/*`), enums, nested objects,
  `#[DataCollection]` items and the `#[Discriminator]` field that selects a concrete class.
- The **contents of the XML document** read by `BaseData::lazyXml()` (`src/Support/XmlStream.php`): third-party feeds are
  the main use case. Entity expansion, external entities, DTD tricks, deep nesting and oversized nodes are all in scope.
- The payload given to `WireableData::fromLivewire()` (`src/Concerns/WireableData.php`) — Livewire state round-trips
  through the browser.
- Request data reaching `HasLaravelIntegration::fromRequest()` and the controller-injection hook in
  `src/Laravel/SimpleDataObjectsServiceProvider.php`.
- The stored value handed to the Eloquent casts (`src/Laravel/DataObjectCast.php`, `DataCollectionCast.php`) and the
  ciphertext handed to `EncryptedCast::get()`.

Treat as **trusted** (written or controlled by the application developer):

- The data classes themselves: property types, defaults and every attribute argument (`#[MapInputName]`, `#[Cast]`,
  `#[Discriminator]` maps, `#[Rules]`, ...).
- Configuration, environment variables, and the metadata cache directory with the `.meta.php` files in it.
- The file path or stream URI passed to `lazyXml()` (the document behind it is untrusted, the URI is not).
- Arguments to the CLI tools in `bin/` and the artisan commands in `src/Laravel/Console/`; these are developer tooling.

## Components that matter most / least

Most important, in this order:

1. **Code generation and `eval()`** — `src/Support/HydratorCompiler.php` and `SerializerCompiler.php` build PHP source
   from class metadata and evaluate it. The design invariant is that only developer-controlled metadata is embedded,
   and free-form strings go through `var_export()`. Any path by which *runtime input* influences generated code is the
   most serious finding this project can have.
2. **Metadata file cache** — `src/Support/MetadataRegistry.php` writes executable PHP (`*.meta.php`) and later
   `require`s it. Invariants: writes are atomic, only an exportable metadata graph is persisted, `clearCache()` only
   ever deletes `*.meta.php`, and class names map to file names without escaping the cache directory.
3. **`EncryptedCast`** (`src/Casts/EncryptedCast.php`) — XSalsa20-Poly1305 via libsodium. Nonce generation, key
   derivation, ciphertext parsing and authentication failures matter. It deliberately has no `__set_state()` so key
   material is never written to the file cache; anything that leaks the key to disk, logs or exception messages is in
   scope.
4. **Class selection from input** — `#[Discriminator]` (`ClassMeta::resolveDiscriminatedClass()`), nested objects and
   collections. Input must only ever select a class from the developer's static map; instantiating an arbitrary class
   named by input would be a serious finding.
5. **XML streaming** — `src/Support/XmlStream.php`. It opens the document with `XMLReader` without entity substitution
   or DTD loading; anything that makes an untrusted document read local files, reach the network, or consume memory
   out of proportion to its size is in scope.
6. **Validation entry points** — `fromValidated()`, `fromValidatedResult()`, `fromRequest()`: a way to obtain a
   hydrated object while skipping the declared `#[Rules]` is in scope.

Less important but still in scope: the remaining casts and pipes, `InputNormalizer`, `TypeScriptGenerator`,
`SchemaGenerator`, and the Laravel console commands.

Out of scope: `tests/`, `docs/`, `vendor/`, `node_modules/`, CI configuration, and
vulnerabilities in dependencies themselves (report those upstream).

## How to exercise it

- `vendor/bin/phpunit --no-coverage` runs the whole suite (about 700 tests, a few seconds).
  `vendor/bin/phpunit --no-coverage tests/XmlStreamTest.php` runs one file.
- `tests/Fixtures/` holds around 150 small data classes covering every attribute; they are the quickest way to build a
  reproducer: `php -r 'require "vendor/autoload.php"; var_dump(StdOut\SimpleDataObjects\Tests\Fixtures\UserData::from($argv[1]));' '{"name":"a","email":"b"}'`.
- Tests that need a booted Laravel application extend `tests/Laravel/TestCase.php` (Orchestra Testbench, in-memory SQLite).
- The file cache is off unless `MetadataRegistry::setStoragePath($dir)` is called; `tests/MetadataCacheTest.php` shows
  how to turn it on. `bin/sdo-warm <cache-dir> <path>...` pre-generates it.

## How we rate severity

- **Critical** — runtime input (not developer-written metadata) reaching `eval()` or a `require`d cache file; input
  causing an arbitrary class to be instantiated or arbitrary code to run; writing or deleting files outside the cache
  directory through input.
- **High** — XXE, local file disclosure or SSRF through an untrusted XML document; recovering plaintext or the key
  from `EncryptedCast`, forging a ciphertext that authenticates, or key material persisted to disk; bypassing
  `#[Rules]` on a validating entry point.
- **Medium** — denial of service with real amplification: a small input causing memory or CPU use far out of
  proportion (entity expansion, pathological nesting, quadratic behaviour); unhandled `TypeError`/`Error` where a
  `DataHydrationException` or `ValidationException` is documented, when it is reachable from the never-throw APIs
  (`tryFrom()`, `fromResult()`, `fromValidatedResult()`).
- **Low** — information disclosure in exception messages (input echoed back, paths), and correctness issues without a
  security consequence.

A finding that requires the attacker to already control the data class source, attribute arguments, configuration,
environment, or to have write access to the cache directory is **not a vulnerability** under this model — say so if a
report depends on it.

## Anything to leave alone

- `from()` does not validate; only the `fromValidated*()` / `fromRequest()` entry points run `#[Rules]`. This is by
  design and documented.
- Scalar coercion is deliberately loose in `IntegerCast`, `FloatCast` and the XML reader (`(int) 'abc'` is `0`). Report
  it only if it leads to something beyond a wrong value.
- `eval()` and `require` of generated code are the library's core mechanism. Their mere presence is not a finding; a
  path from untrusted input into them is.
- Memory use that is linear in the size of the input is expected. `collection()` materializes everything on purpose —
  `lazyCollection()` and `lazyXml()` are the streaming variants.
- `fromLazy()` defers hydration, so invalid input throws on first property access rather than at the call. Documented.

## Reports and patches

Please include a self-contained PHP reproducer (a data class plus the input) and, for patches, a PHPUnit test under
`tests/` — the project enforces 100% line coverage, so a fix without a test will not be merged as is. Patches must not
add reflection or per-call allocations to the `from()` / `toArray()` hot path.
