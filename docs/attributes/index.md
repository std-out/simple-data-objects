# Attributes Overview

All behaviour in Simple Data Objects is declared via PHP attributes on constructor parameters (or on the class itself for `#[TransformKeys]`/`#[Pipe]`). Every parameter-level attribute also works on a plain property for [constructor-less and hybrid DTOs](../features/hydration.md#constructor-less-dtos) — same syntax, same behavior either way.

| Attribute | Target | Purpose |
|---|---|---|
| [`#[Cast]`](./cast.md) | Parameter or property | Apply a type cast during hydration and serialization |
| [`#[Rules]`](./rules.md) | Parameter or property | Laravel validation rules |
| [`#[Flatten]`](./flatten.md) | Parameter or property | Inline nested DTO fields into the parent array |
| [`#[Hidden]`](./hidden.md) | Parameter or property | Exclude from `toArray()` / JSON output |
| [`#[IgnoreIfNull]`](./ignore-if-null.md) | Parameter or property | Omit from output when value is `null` |
| [`#[MapPropertyName]`](./map-property-name.md) | Parameter or property | Map a different input key to this property |
| [`#[TransformKeys]`](./transform-keys.md) | Class | Transform all input keys at the class level |
| [`#[DataCollection]`](./data-collection.md) | Parameter or property | Declare a typed collection property |
| [`#[Pipe]`](./pipe.md) | Class, parameter, or property | Input preprocessing middleware — whole array (class) or a single value (parameter/property) |
