# Attributes Overview

All behaviour in Simple Data Objects is declared via PHP attributes on constructor parameters (or on the class itself for `#[TransformKeys]`).

| Attribute | Target | Purpose |
|---|---|---|
| [`#[Cast]`](/attributes/cast) | Parameter | Apply a type cast during hydration and serialization |
| [`#[Rules]`](/attributes/rules) | Parameter | Laravel validation rules |
| [`#[Flatten]`](/attributes/flatten) | Parameter | Inline nested DTO fields into the parent array |
| [`#[Hidden]`](/attributes/hidden) | Parameter | Exclude from `toArray()` / JSON output |
| [`#[IgnoreIfNull]`](/attributes/ignore-if-null) | Parameter | Omit from output when value is `null` |
| [`#[MapPropertyName]`](/attributes/map-property-name) | Parameter | Map a different input key to this property |
| [`#[TransformKeys]`](/attributes/transform-keys) | Class | Transform all input keys at the class level |
| [`#[DataCollection]`](/attributes/data-collection) | Parameter | Declare a typed collection property |
