# Generating classes from a sample

Describing a large payload by hand — an order with a customer, addresses, line items and options — is slow and error-prone. Give the generator one real example instead, and it writes the classes: one per object shape, with property types, nested DTOs, collections and key mapping inferred from the values.

Sources:

- a JSON file, or JSON piped in on STDIN;
- an XML file (the result is ready for [`lazyXml()`](./xml.md));
- any PHP array or object — `DataClassGenerator::fromValue()`;
- an Eloquent record with its loaded relations — `make:data --from-model --id`.

It is a development tool: the output is plain PHP you review and commit. Nothing runs at hydration time.

## From a JSON file

```json
{
  "id": 1042,
  "order_number": "A-1042",
  "total": 199.9,
  "tags": ["vip", "gift"],
  "customer": {"first_name": "Ada", "address": {"city": "Kyiv"}},
  "items": [
    {"sku": "A1", "price": 10, "options": {"color": "red"}},
    {"sku": "B2", "price": 9.5, "discount": 1.5}
  ]
}
```

```sh
vendor/bin/sdo-generate storage/samples/order.json app/Data
```

```
  generated  App\Data\AddressData
  generated  App\Data\CustomerData
  generated  App\Data\OptionsData
  generated  App\Data\ItemData
  generated  App\Data\OrderData

5 class(es) written to app/Data — hydrate with OrderData::from($data)
```

```php
#[TransformKeys(TransformKeys::SNAKE_CASE)]
class OrderData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly float $total,
        public readonly array $tags,
        public readonly CustomerData $customer,
        #[DataCollection(ItemData::class)]
        public readonly TypedDataCollection $items,
    ) {}
}

class ItemData extends BaseData
{
    public function __construct(
        public readonly string $sku,
        public readonly float $price,
        public readonly ?OptionsData $options = null,
        public readonly ?float $discount = null,
    ) {}
}
```

The namespace comes from the PSR-4 map in `./composer.json`; pass `--namespace=` for a directory outside it. The root class is named after the file (`orders.json` holding a list gives `OrderData`); pass `--class=` to choose.

```sh
curl -s https://api.example.com/orders/1 | vendor/bin/sdo-generate - app/Data --class=OrderData
vendor/bin/sdo-generate order.json app/Data --dry-run     # print instead of writing
vendor/bin/sdo-generate order.json app/Data --force       # overwrite existing files
```

## How types are inferred

Every object of a list is merged, so a list of a hundred orders gives better types than one.

| Seen in the sample | Generated |
|---|---|
| `1`, `2` | `int` |
| `10` and `9.5` | `float` |
| `1` and `"a"` | `int\|string` |
| a value and `null` | `?string` (nullable, still required) |
| a key missing from some objects | `?float $discount = null`, moved to the end — or `float\|Optional` with `--optional` |
| only `null`, or incompatible values (`{...}` and `5`) | `mixed` |
| `{...}` | a nested class named after the key: `customer` → `CustomerData` |
| `[{...}, {...}]` | `#[DataCollection(ItemData::class)] TypedDataCollection`, singular name: `items` → `ItemData` |
| `[1, 2]`, `[]`, `{}` | `array` |

Keys:

- `snake_case` keys throughout get one class-level `#[TransformKeys(TransformKeys::SNAKE_CASE)]` and camelCase properties.
- Any other key that isn't a valid property name gets `#[MapPropertyName]`: `created-at` → `$createdAt`, `2fa` → `$field2fa`.
- `toArray()` gives the original keys back.

Objects with the same fields share one class. Two different shapes under the same key get distinct names: `AddressData`, then `BillingAddressData`.

### `--optional`: exact round trip

By default a key that only some objects have becomes `?T = null`, which is convenient to read but serializes as `null` where the sample had no key. With `--optional` it becomes `T|Optional` instead: a missing key stays missing, and `from($sample)->toArray()` returns the sample unchanged. See [Optional values](./optional.md).

## From an XML file

```xml
<catalog xmlns:g="http://base.google.com/ns/1.0">
  <offer id="1" available="true">
    <name>Phone</name>
    <price currency="UAH">999.50</price>
    <g:brand>Acme</g:brand>
    <picture>a.jpg</picture>
    <picture>b.jpg</picture>
    <param name="Color">Red</param>
    <param name="Size">L</param>
  </offer>
  <offer id="2" available="false">…</offer>
</catalog>
```

```sh
vendor/bin/sdo-generate feed.xml app/Data
# 3 class(es) written to app/Data — hydrate with OfferData::lazyXml($file, 'catalog/offer')
```

```php
class OfferData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly int $id,
        #[XmlAttribute]
        public readonly bool $available,
        public readonly string $name,
        public readonly PriceData $price,
        #[XmlElement('g:brand')]
        public readonly string $gBrand,
        #[XmlElement('picture')]
        public readonly array $pictures,
        #[DataCollection(ParamData::class)]
        #[XmlElement('param')]
        public readonly TypedDataCollection $params,
    ) {}
}

class PriceData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly string $currency,
        #[XmlText]
        public readonly float $value,
    ) {}
}
```

- The record element is the repeated child of the root (`catalog/offer`), or the root itself. Pass `--xml-path=catalog/shop/offers/offer` for deeper feeds.
- Every record is read, streaming, so the sample can be the full feed.
- Text is typed by its content: `3` → `int`, `999.50` → `float`, `true`/`false` → `bool`. Anything else, or a mix, is `string`. An empty element makes the property nullable.
- An element that repeats in any record becomes a list with a plural property name.
- An element with attributes becomes a nested class with `#[XmlAttribute]` properties, plus `#[XmlText]` for its text.

## From a PHP array or object

Anything `json_encode()` accepts works: arrays, `JsonSerializable`, Laravel collections and models, API client responses.

```php
use StdOut\SimpleDataObjects\Support\DataClassGenerator;

$response = $client->get('/orders/1')->json();

$classes = DataClassGenerator::fromValue($response, 'OrderData', 'App\\Data');
DataClassGenerator::write($classes, app_path('Data'));
```

`fromValue()` returns `class name => PHP source` without touching the disk. `write()` refuses to overwrite existing files unless `force: true` is passed. Both take `optional: true` the same way as the CLI.

## From an Eloquent record (Laravel)

`make:data --from-model` with `--id` generates from a real record instead of the table columns, so relations become nested classes and collections:

```sh
php artisan make:data OrderData --from-model=Order --id=1042 --with=customer,items.product
```

The record is serialized with `toArray()`, so the model's `$hidden` attributes are left out and `$casts` decide the types. Only relations named in `--with` are loaded. `--from-json`, `--from-xml` (with `--xml-path`), `--optional` and `--force` work the same as the CLI. See [`make:data`](../laravel/service-provider.md#make-data).

## Review the result

A sample only shows what it contains:

- a field that happens to be `null` in every object is typed `mixed`;
- a list that is always empty is a plain `array`;
- an XML element that never repeats in the sample is not a list.

Pick a sample with filled-in values, or merge several records into a list. Then adjust what the data doesn't say: [`#[Rules]`](../attributes/rules.md), enums, `#[Cast]` for dates and money, [`#[Sensitive]`](../attributes/sensitive.md) for card data.
