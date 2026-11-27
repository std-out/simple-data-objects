# Upgrading from 2.x to 3.0

> **Work in progress.** 3.0 is being built on the `3.x` branch, one pull
> request per change (milestone [3.0.0](https://github.com/std-out/simple-data-objects/milestone/1)).
> This guide is updated in the same pull request as each change and is final
> only when 3.0.0 is tagged. To try it: `composer require std-out/simple-data-objects:3.0.x-dev`.

3.0 changes how input is validated and coerced. Every change makes the
library reject input that 2.x accepted silently or turned into something
else. No public API is removed. Most applications need no code changes,
only a check of the classes each section names.

The repository ships a [`migrate-from-v2-to-v3` skill](.claude/skills/migrate-from-v2-to-v3/SKILL.md)
for Claude Code that walks a codebase through this guide class by class.

## Before you upgrade

1. Update to the latest 2.x (2.9 or later) and run the test suite.
2. Check the `deprecations` log channel (Laravel) or run your tests with
   suppressed deprecations reported (PHPUnit
   `<source ignoreSuppressionOfDeprecations="true">`). Every 2.9 deprecation
   is an error in 3.0.
3. Run your tests with a JSON/form payload for every endpoint that uses
   `fromValidated()`, `validate()` or `fromRequest()`. Those are the calls
   whose behaviour changes most.

## Nested `#[Rules]` apply without `#[InferRules]`

**2.x:** `#[Rules]` on a nested data class, or on the item class of a
`#[DataCollection]`, were checked only when the parent class had
`#[InferRules]`. Without it they were ignored.

**3.0:** a nested class's rules always apply when the nested value is
present, whether or not the parent has `#[InferRules]`.

**Affected:** a class with a property typed as another data class (or a
`#[DataCollection]`) whose class has `#[Rules]`, validated through
`fromValidated()`, `validate()`, `fromValidatedResult()` or `fromRequest()`,
where the parent has no `#[InferRules]`.

**What to do:** nothing, if the nested rules are correct. Input that broke
them now fails validation instead of passing. If a nested rule was never
meant to apply in that context, remove it or move it to the parent.

## Pipes run before validation

**2.x:** `fromValidated()` validated the raw input, then `from()` ran the
pipes. `'  ab '` passed `min:3` and was trimmed to `'ab'` afterwards.

**3.0:** `fromValidated()`, `validate()` and `fromValidatedResult()` run the
class and property pipes first, validate the piped values, and hydrate from
them without running the pipes again. `from()` is unchanged.

**Affected:** any class with both `#[Pipe]` and `#[Rules]` (or
`#[InferRules]`).

**What to do:**

- Write rules for the value a pipe produces, not the raw input. A pipe that
  turns `"1,2"` into `[1, 2]` now needs an `array` rule, not a `string` rule.
- A pipe now sees invalid input before any rule rejects it. Make it return
  unexpected types unchanged instead of failing on them. A pipe that still
  throws is reported as a validation error under `'$pipeline'`.

## Scalar properties are coerced without loss

**2.x:** generated code ran without `strict_types`, so PHP's own coercion
applied: `int` took `"1.9"` and `1.9` as `1`, and `true` as `1`; `bool` took
`"false"` as `true`; `int` given `"abc"` threw a `TypeError` that named a
library file.

**3.0:** a property typed `int`, `float`, `string` or `bool` without a cast
takes only values that convert without loss:

| Type | Accepted | Rejected |
|---|---|---|
| `int` | int, a float with no fraction, a string of digits with an optional `-` within range | `"1.9"`, `1.9`, `true`, `"abc"` |
| `float` | int, float, a numeric string | `true`, `"abc"` |
| `string` | string, int, float | `true`, arrays, objects |
| `bool` | bool, and `"1"`, `"0"`, `"true"`, `"false"`, `"on"`, `"off"`, `"yes"`, `"no"`, `""`, `1`, `0` | `"maybe"`, `2` |

Anything else throws `DataHydrationException` naming the field; `tryFrom()`
returns `null` and `fromResult()` reports it under the field's key.

**Affected:** inputs that relied on lossy coercion: decimals into `int`,
`"false"`/`"no"` strings into `bool` (now `false`, previously `true`).

**What to do:** type the property as `float`, add a cast (`IntegerCast`,
`BooleanCast`), or fix the input.

## Invalid enum values on nullable properties throw

**2.x:** a value that matched no case became `null` on a `?Status`
property; a non-nullable property already threw.

**3.0:** a non-null value that matches no case always throws
`DataHydrationException`. `null` stays `null`.

**What to do:** send `null` (or omit the key with a default) instead of an
unknown value, or validate the value with a rule first.

## Casts throw `DataHydrationException`

**2.x:** given a value of the wrong type, several casts raised PHP errors or
warnings: `UuidCast` given an array emitted "Array to string conversion"
before its own exception; `TrimCast`, `BooleanCast`, `DateTimeCast`,
`CommaSeparatedCast`, `MoneyCast` and `#[DataCollection]` raised `Error` or
`TypeError`.

**3.0:** a cast that cannot handle its input throws
`DataHydrationException` naming the field, with the cast's own exception as
`getPrevious()`. No warnings are emitted.

**What to do:** catch `DataHydrationException` (or use `tryFrom()` /
`fromResult()`) where you caught `TypeError`, `Error` or
`InvalidArgumentException` around hydration. Custom casts are wrapped the
same way; nothing to change in them.

## `FloatCast` rejects `INF` and `NAN` by default

**2.x:** `new FloatCast` accepted `INF` and `NAN` (for example from a JSON
`1e400`); rejecting them was opt-in with `finite: true`.

**3.0:** `finite` defaults to `true`. Pass `finite: false` to keep accepting
them.

## Weak legacy `EncryptedCast` keys are rejected

**2.x:** a key given through `$key`, `$env` or `$provider` (the format
without `keyring:`) could be any string. 2.9 emits `E_USER_DEPRECATED` for a
key that is not 32 random bytes.

**3.0:** such a key throws `InvalidArgumentException` when it is the only
key source. It is still accepted next to `keyring:`, for reading values
encrypted with it; new values are always written with the keyring.

**What to do,** for each `EncryptedCast` that logged the 2.9 deprecation:

1. Add a keyring with a strong current key (`base64_encode(random_bytes(32))`),
   keep the old key as `$env`/`$key` next to it, and deploy on 2.x.
2. Re-encrypt stored values over time: `needsReencryption()` tells which
   ones still use the old format.
3. Upgrade to 3.0. Once nothing needs re-encryption, drop the old key.

A key that is already 32 random bytes (raw or base64) keeps working as
before.
