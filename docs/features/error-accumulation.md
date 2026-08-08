# fromResult() — Error Accumulation

`from()`/`tryFrom()` are fail-fast: the first invalid field throws (or `tryFrom()` swallows it and returns `null`, losing the reason). `fromResult()` is the "safe parse" alternative — analogous to zod's `safeParse` or pydantic's `ValidationError` — it never throws, tries every field, and returns every problem at once.

```php
use StdOut\SimpleDataObjects\HydrationResult;

$result = OrderData::fromResult($input);

$result->ok();          // bool
$result->value();       // OrderData — throws LogicException if !ok()
$result->valueOrNull(); // OrderData|null
$result->errors();      // ['deliveryDate' => 'Invalid date format', 'items.2.price' => '...']
```

## Dot-path errors for nested DTOs and collections

A nested `BaseData` property or a `#[DataCollection]` recurses into the target class's own `fromResult()`, so a failure several levels deep is still reported with a precise path:

```php
class OrderData extends BaseData
{
    public function __construct(
        public readonly string $customerName,
        public readonly AddressData $shippingAddress,
        #[DataCollection(ItemData::class)]
        public readonly TypedDataCollection $items,
    ) {}
}

$result = OrderData::fromResult([
    'customerName' => 'Ada',
    'shippingAddress' => ['street' => '1 Ave'],       // missing 'city'
    'items' => [
        ['sku' => 'A', 'price' => 10],
        ['sku' => 'B'],                                // missing 'price'
    ],
]);

$result->errors();
// [
//   'shippingAddress.city' => "Missing required field 'city' for AddressData.",
//   'items.1.price' => "Missing required field 'price' for ItemData.",
// ]
```

[`#[Flatten]`](../attributes/flatten.md) fields are the one exception: since a flattened DTO's keys already live in the parent's own namespace, its errors merge in without a prefix.

## What's never a per-field error

- `#[RejectUnknownKeys]` reports unrecognized keys as a single `'$unknown'` entry, alongside any field errors — it doesn't abort the rest of the accumulation.
- A failing class-level [`#[Pipe]`](../attributes/pipe.md) reports a single `'$pipeline'` entry. Since the array transform itself failed, per-field extraction is skipped for that call — there's nothing reliable left to read.
- An exception from the constructor itself (e.g. an invariant check in the constructor body) is reported as `'$construct'`.
- Invalid non-array input (a malformed JSON string, or a type `InputNormalizer` can't convert) is reported as `'$input'`.

## fromValidatedResult() — merge in Rules validation too

Runs `fromResult()` and, if the class declares `#[Rules]` (or [`#[InferRules]`](../attributes/infer-rules.md)), also validates the raw input and merges in the first message per failing rule. A validation message wins over a hydration message on the same key.

```php
$result = OrderData::fromValidatedResult($request->all());

if (! $result->ok()) {
    return response()->json(['errors' => $result->errors()], 422);
}

$order = $result->value();
```

## Performance

`fromResult()` compiles its own specialized closure per class — the same code-generation strategy `from()` uses, not an interpreted walk over metadata — so it stays fast even though it never throws. It's compiled lazily and cached separately from `from()`'s hydrator: classes that never call `fromResult()` pay nothing for it, and `from()`/`tryFrom()` are completely unaffected either way.
