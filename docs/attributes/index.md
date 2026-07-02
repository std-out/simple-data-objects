# Attributes Overview

All behaviour in Simple Data Objects is declared via PHP attributes on constructor parameters (or on the class itself for `#[TransformKeys]`).

| Attribute | Target | Purpose |
|---|---|---|
| [`#[Cast]`](./cast.md) | Parameter | Apply a type cast during hydration and serialization |
| [`#[Rules]`](./rules.md) | Parameter | Laravel validation rules |
| [`#[Flatten]`](./flatten.md) | Parameter | Inline nested DTO fields into the parent array |
| [`#[Hidden]`](./hidden.md) | Parameter | Exclude from `toArray()` / JSON output |
| [`#[IgnoreIfNull]`](./ignore-if-null.md) | Parameter | Omit from output when value is `null` |
| [`#[MapPropertyName]`](./map-property-name.md) | Parameter | Map a different input key to this property |
| [`#[TransformKeys]`](./transform-keys.md) | Class | Transform all input keys at the class level |
| [`#[DataCollection]`](./data-collection.md) | Parameter | Declare a typed collection property |
