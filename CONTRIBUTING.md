# Contributing to Simple Data Objects

Thanks for taking the time to contribute! Whether it's a bug report, a new
cast, a docs fix, or a performance win — every contribution is welcome, and
small PRs are just as valued as big ones.

## Ways to contribute

- **Report a bug** — open an issue with a minimal reproducible DTO + input.
- **Propose a feature** — open an issue first so we can agree on the design
  before you invest time in code.
- **Improve the docs** — the docs site lives in `docs/` (VitePress); typo
  fixes are one-click PRs.
- **Add a cast or pipe** — the most self-contained way to contribute code.
  Look at `src/Casts/TrimCast.php` or `src/Pipes/TrimValuePipe.php` for the
  pattern.

## Development setup

You need either **Docker** (recommended, zero local PHP required) or a local
**PHP 8.4+** with Composer.

### Docker (recommended)

```bash
git clone https://github.com/std-out/simple-data-objects.git
cd simple-data-objects
make build      # build the container (installs dependencies)
make test       # run the test suite
make coverage   # run tests with the 100% coverage gate
make lint       # auto-fix code style (Laravel Pint)
make shell      # open a shell inside the container
```

### Local PHP

```bash
composer install
composer test              # phpunit, no coverage
composer test:coverage     # phpunit + clover report (needs pcov or xdebug)
composer coverage:check    # enforce the 100% threshold
composer lint              # pint --fix
composer lint:check        # pint --test (what CI runs)
```

## The quality bar

These are checked by CI on every PR — running them locally first saves a
round-trip:

1. **Tests pass** on all supported Laravel versions (10–13).
2. **100% line coverage, no exceptions.** The gate is
   `bin/check-coverage.php` at threshold 100, and there are no
   `@codeCoverageIgnore` annotations in the codebase. If your change adds a
   branch, it adds a test.
3. **Pint-clean code style** — `composer lint` fixes it automatically.

## Performance guidelines

The hot path (`from()` / `toArray()`) is **compiled**: each data class gets a
generated closure where plain properties are inline array reads. That design
carries two rules for contributors:

- **No reflection and no per-call allocation on the hot path.** Anything
  expensive belongs on the cold path (first hydration of a class), typically
  in `ClassMetaFactory`, `InputNormalizer`, or the compilers.
- **Performance-sensitive PRs need numbers.** If you touch
  `HydratorCompiler`, `SerializerCompiler`, `ValueCaster`, or
  `TypedDataCollection`, include a simple before/after micro-benchmark
  (a loop over `::from()` / `->toArray()` with `hrtime()` is enough) in the
  PR description.

## Security-sensitive areas

Two parts of the codebase have security invariants — changes there get extra
review scrutiny:

- **`src/Casts/EncryptedCast.php`** — XSalsa20-Poly1305 via libsodium. Don't
  change nonce handling, key derivation, or ciphertext format without
  discussing in an issue first (format changes break existing stored data).
- **`src/Support/MetadataRegistry.php` cache files (`.meta.php`)** — these
  are executable PHP served from the cache directory. Writes must stay atomic
  and the exportability guard must stay in place.

Found a vulnerability? **Please don't open a public issue.** Email
[yuriyzee@gmail.com](mailto:yuriyzee@gmail.com) and we'll coordinate a fix
and disclosure.

## Pull request checklist

- [ ] One logical change per PR — small PRs get reviewed fast.
- [ ] Tests added/updated; `make coverage` (or `composer coverage:check`) passes.
- [ ] `composer lint:check` passes.
- [ ] Docs updated if behavior changed (`docs/` and/or `README.md`).
- [ ] Entry added under **[Unreleased]** in `CHANGELOG.md`
      (following [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)).
- [ ] Commit messages explain *why*, not just *what*.

We use [Semantic Versioning](https://semver.org): breaking changes → major,
new features → minor, fixes → patch. Note in the PR description if your
change is breaking.

## Working on the docs

```bash
npm install
npm run docs:dev      # live-reload dev server
npm run docs:build    # production build (what the Pages workflow runs)
```

## Code of conduct

Be kind, be constructive, assume good intent. Critique code, not people.
Maintainers reserve the right to remove content that doesn't meet that bar.

---

*Not sure where to start? Issues labeled `good first issue` are curated for
first-time contributors — and if none are open, improving test descriptions
or docs examples is always appreciated.*
