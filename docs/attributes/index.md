# Attributes Overview

All behaviour in Simple Data Objects is declared via PHP attributes on constructor parameters (or on the class itself for `#[TransformKeys]`/`#[Pipe]`/`#[Discriminator]`/`#[RejectUnknownKeys]`/`#[InferRules]`, or on a method for `#[Computed]`). Every parameter-level attribute also works on a plain property for [constructor-less and hybrid DTOs](../features/hydration.md#constructor-less-dtos) — same syntax, same behavior either way.

| Attribute | Target | Purpose |
|---|---|---|
| [`#[Cast]`](./cast.md) | Parameter or property | Apply a type cast during hydration and serialization |
| [`#[Rules]`](./rules.md) | Parameter or property | Laravel validation rules |
| [`#[InferRules]`](./infer-rules.md) | Class | Auto-generate validation rules from property types |
| [`#[Flatten]`](./flatten.md) | Parameter or property | Inline nested DTO fields into the parent array |
| [`#[Hidden]`](./hidden.md) | Parameter or property | Exclude from `toArray()` / JSON output |
| [`#[IgnoreIfNull]`](./ignore-if-null.md) | Parameter or property | Omit from output when value is `null` |
| [`#[Computed]`](./computed.md) | Method | Add a derived, method-backed field to serialization output |
| [`#[MapPropertyName]`](./map-property-name.md) | Parameter or property | Map a different input key (or several aliases) to this property |
| [`#[MapInputName]` / `#[MapOutputName]`](./map-input-output-name.md) | Parameter or property | Map hydration and serialization keys independently |
| [`#[TransformKeys]`](./transform-keys.md) | Class | Transform all input keys at the class level |
| [`#[Discriminator]`](./discriminator.md) | Class (abstract) | Polymorphic hydration — dispatch `from()` to a concrete subclass by field value |
| [`#[DataCollection]`](./data-collection.md) | Parameter or property | Declare a typed collection property |
| [`#[Pipe]`](./pipe.md) | Class, parameter, or property | Input preprocessing middleware — whole array (class) or a single value (parameter/property) |
| [`#[WhenLoaded]`](./when-loaded.md) | Parameter or property | Include an Eloquent relation in `fromModel()` only when it's loaded |
| [`#[RejectUnknownKeys]`](./reject-unknown-keys.md) | Class | Strict mode — throw when input contains a key the class doesn't recognize |
| [`#[WrapIn]`](./wrap-in.md) | Class | Wrap `toResponse()`'s payload under a key (`toArray()`/`toJson()` unaffected) |
