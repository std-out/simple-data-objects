---
name: migrate-from-v2-to-v3
description: Upgrade a PHP/Laravel codebase from std-out/simple-data-objects 2.x to 3.0 — inventories every data class affected by the 3.0 behaviour changes (nested #[Rules] without #[InferRules], pipes before validation, lossless scalar coercion, invalid enums on nullable properties, cast exceptions, FloatCast finite default, weak legacy EncryptedCast keys), adapts each one, flags data-dependent decisions for human review, and verifies with the test suite. Use when the user asks to upgrade, migrate or move simple-data-objects (or "SDO") from version 2 to version 3.
---

# Upgrade Simple Data Objects from 2.x to 3.0

3.0 removes no public API. Every change makes the library reject input that
2.x accepted or silently changed, so the risk is in **data**, not in code
that fails to compile. Work change by change, run the tests after each one,
and stop to ask the human at every **STOP AND ASK** instead of guessing:
whether input that 3.0 rejects is a bug or a real client is the owner's call.

The authoritative description of every change is `UPGRADE-3.0.md` in the
package (`vendor/std-out/simple-data-objects/UPGRADE-3.0.md`). Read it first.

## Before starting

1. Read `composer.json` and `composer.lock`. If the installed version is
   below 2.9, upgrade to the latest 2.x first and get the tests green there
   — 2.9 adds the deprecation notices this skill relies on.
2. Run the test suite on 2.x and record the result. If it is not green,
   **STOP AND ASK** — pre-existing failures must not be blamed on the upgrade.
3. Collect 2.9 deprecations: in Laravel read the `deprecations` log channel
   (`storage/logs`, or the channel configured in `config/logging.php`); in
   PHPUnit set `<source ignoreSuppressionOfDeprecations="true">` and rerun.
   Note every `simple-data-objects 2.9` message.
4. **Encryption keys first.** If any `EncryptedCast` logged the weak-key
   deprecation, do Step 7 on 2.x and deploy it **before** changing the
   version constraint. Upgrading first locks the application out of data
   encrypted with that key.
5. Change the constraint (`"std-out/simple-data-objects": "^3.0"`), run
   `composer update std-out/simple-data-objects`, then clear the metadata
   cache: `php artisan sdo:clear` (Laravel) or
   `MetadataRegistry::clearCache()`.

## Step 1 — inventory

```
grep -rln "StdOut\\\\SimpleDataObjects" --include=*.php app src lib 2>/dev/null
```

For every data class (`extends BaseData`) record: nested data-class or
`#[DataCollection]` properties, `#[Rules]` / `#[InferRules]`, `#[Pipe]`,
scalar-typed properties without `#[Cast]`, enum properties, `#[Cast]` usages,
`EncryptedCast`. Record every call site of `fromValidated(`, `validate(`,
`fromValidatedResult(`, `fromRequest(` and controller injection of data
classes. Steps 2 and 3 only matter for classes reached through those calls.

## Step 2 — nested `#[Rules]` without `#[InferRules]`

Find parents **without** `#[InferRules]` that have a property typed as a data
class (or `#[DataCollection(X::class)]`) where that class, or anything it
nests, has `#[Rules]`. In 3.0 those nested rules apply.

For each one, write a test that posts the real payload shape through the
validating call site. If it now fails, **STOP AND ASK**: is the nested rule
correct (the payload is the bug) or was it never meant for this endpoint
(remove or move the rule)? Do not loosen rules on your own.

## Step 3 — pipes run before validation

Find classes with both `#[Pipe]` (class or property) and rules. For each pipe:

- **Rules must describe the piped value.** If the pipe changes the type or
  shape (string → array, string → int, nesting), update the rules for that
  field. If it only normalises (trim, lowercase), the rules usually stay but
  can now be tighter.
- **The pipe now sees unvalidated input.** Read its `handle()`; if it assumes
  a type (calls `trim()`, `explode()`, indexes an array), make it pass other
  types through unchanged (`is_string($value) ? trim($value) : $value`).
  A pipe that throws becomes a validation error under `'$pipeline'`.

Add a test with input that only passes after the pipe (e.g. `'  abc '` for
`min:3` + trim) and one that the pipe cannot handle (e.g. an array).

## Step 4 — scalar coercion

Find scalar-typed constructor properties (`int`, `float`, `string`, `bool`,
nullable or not) **without** `#[Cast]`. 3.0 accepts only lossless input — see
the table in `UPGRADE-3.0.md`. Look at where the data comes from:

- **Forms / query strings:** digits into `int` still work. Decimals into
  `int` and `"false"`/`"no"` into `bool` change: the first now throws, the
  second becomes `false` (it was `true`).
- **JSON APIs:** `1.0` into `int` still works; `1.5` throws.
- **Database rows / internal arrays:** usually already typed.

If a property legitimately receives lossy input, **STOP AND ASK** before
choosing between retyping it (`float`), adding a cast (`IntegerCast`,
`BooleanCast`) or rejecting the input. Never add a cast just to make a test
pass without asking.

## Step 5 — enums on nullable properties

Find nullable enum properties (`?Status`, `Status|null`). An unknown value
now throws instead of becoming `null`. If clients send unknown values on
purpose (e.g. forward compatibility), **STOP AND ASK** — the options are a
rule (`Rule::enum`) that rejects them with a message, or a pipe that maps
them to `null` explicitly.

## Step 6 — exceptions from hydration

Search for code catching the old exception types around hydration:

```
grep -rnE "catch \((\\\\)?(TypeError|Error|InvalidArgumentException|ValueError|Throwable)" --include=*.php app src
```

Where the `try` block calls `from(`, `fromJson(`, `collection(`, `with(` or
`fromModel(`, catch `StdOut\SimpleDataObjects\Exceptions\DataHydrationException`
instead (cast failures now arrive as that type, with the original as
`getPrevious()`), or switch to `tryFrom()` / `fromResult()`. Leave unrelated
catches alone.

`FloatCast`: find `new FloatCast` without `finite:`. 3.0 rejects `INF`/`NAN`.
Only if a field must store them, add `finite: false` — **STOP AND ASK** first.

## Step 7 — weak legacy `EncryptedCast` keys (do this on 2.x)

Find every `EncryptedCast` built with `key:`, `env:` or `provider:` and no
`keyring:`, and check the key it resolves to. It is strong only if it is 32
random bytes, raw or base64/base64url. Never print or log the key value;
check length and decodability only.

For each weak key:

1. Create an `EncryptionKeyring` implementation with a new key
   (`base64_encode(random_bytes(32))`) stored like the application's other
   secrets. **STOP AND ASK** where the human wants it stored (env, vault,
   KMS); do not invent a location.
2. Change the cast to `new EncryptedCast(env: 'OLD_KEY', keyring: AppKeyring::class)`
   so old values stay readable and new ones use the keyring.
3. Deploy on 2.x. Re-encrypt over time where `needsReencryption()` is true
   (a queued job or a migration over the table). **STOP AND ASK** before
   writing anything that rewrites stored data in bulk.

In 3.0 the old weak key keeps working only next to `keyring:`.

## Step 8 — verify

1. Run the full test suite. Every new failure must map to one of Steps 2–7;
   if one does not, **STOP AND ASK** — it is not an expected 3.0 change.
2. Run static analysis if the project has it.
3. Report to the human: classes changed per step, decisions taken at each
   STOP AND ASK, and any endpoint whose accepted input got stricter.
