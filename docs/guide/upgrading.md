# Upgrading from 2.x

3.0 changes how input is validated and coerced. Each change makes the library reject input that 2.x accepted silently or turned into something else. No public API is removed:

- the `#[Rules]` of nested data classes apply without `#[InferRules]`;
- pipes run before validation;
- `int`, `float`, `string` and `bool` properties take only values that convert without loss;
- an invalid value for a nullable enum property throws;
- cast failures are reported as `DataHydrationException`;
- `FloatCast` rejects `INF` and `NAN` by default;
- a weak legacy `EncryptedCast` key is rejected unless it sits next to a keyring.

[`UPGRADE-3.0.md`](https://github.com/std-out/simple-data-objects/blob/main/UPGRADE-3.0.md) describes each change, which classes it affects and what to do.

## With Claude Code

The repository ships a [`migrate-from-v2-to-v3` skill](https://github.com/std-out/simple-data-objects/blob/main/.claude/skills/migrate-from-v2-to-v3/SKILL.md). Copy it into your project's `.claude/skills/` and ask Claude Code to upgrade simple-data-objects to 3.0. It finds every affected data class, adapts it, stops for decisions that depend on your data, and runs your tests.

The 2.x documentation stays available at [/v2/](https://std-out.github.io/simple-data-objects/v2/).
