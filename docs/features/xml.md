# Streaming XML

`lazyXml()` reads a large XML file **one element at a time** and hydrates each into a DTO as the collection is consumed. The document is never loaded into memory as a whole — peak memory stays flat whether the file holds a hundred nodes or ten million.

```php
OfferData::lazyXml($file, 'catalog/shop/offers/offer')
    ->filter(fn (OfferData $o) => $o->available)
    ->chunk(500)
    ->each(fn ($chunk) => Offer::upsert($chunk->map->toArray()->all(), ['id']));
```

The first argument is anything `XMLReader` can open — a path or a stream wrapper such as `compress.zlib://feed.xml.gz`. The second is the slash-separated path of element names, from the root, to the repeated element.

The result is a regular `Illuminate\Support\LazyCollection` of fully typed `OfferData`, so it can be iterated with `foreach`, and `take()`, `filter()`, `chunk()`, `each()` and friends stop reading the file as soon as they have enough.

Requires `ext-xmlreader` (bundled with PHP by default).

## Describing the structure

**One element, one DTO.** A DTO describes exactly one element — its attributes, its text and its direct children. A child that has structure of its own (attributes or children) is a nested DTO, the same way nested data works everywhere else in this package. A property never reaches into a grandchild.

A property reads the child element with its own name. Three attributes say otherwise:

| Attribute | The property reads |
|---|---|
| *(none)* | the child element named like the property — its text for a scalar, the whole element for a nested DTO |
| `#[XmlAttribute]` | the XML attribute named like the property; `#[XmlAttribute('uom')]` for a different name |
| `#[XmlElement('picture')]` | the child element(s) with that name, when it differs from the property name |
| `#[XmlText]` | the element's own text — for an element that also carries attributes |

They only affect `lazyXml()`: the same class still hydrates from a plain array under its usual keys, and serializes as usual. See the [attribute reference](../attributes/xml.md).

```xml
<offer id="42" available="true">
    <name>Kettle</name>
    <price currency="UAH">499.90</price>
    <picture>https://example.com/a.jpg</picture>
    <picture>https://example.com/b.jpg</picture>
    <param name="Color">Red</param>
    <param name="Volume">1.7 L</param>
    <vendor code="BSH"><name>Bosch</name></vendor>
    <delivery>
        <option cost="80" days="2"/>
        <option cost="0" days="7"/>
    </delivery>
</offer>
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
        #[XmlElement('picture')]
        public readonly array $pictures,
        #[DataCollection(ParamData::class)]
        #[XmlElement('param')]
        public readonly TypedDataCollection $params,
        public readonly ?VendorData $vendor = null,
        public readonly ?DeliveryData $delivery = null,
    ) {}
}

class PriceData extends BaseData
{
    public function __construct(
        #[XmlText]
        public readonly float $amount,
        #[XmlAttribute]
        public readonly string $currency,
    ) {}
}

class DeliveryData extends BaseData
{
    public function __construct(
        #[DataCollection(DeliveryOptionData::class)]
        #[XmlElement('option')]
        public readonly TypedDataCollection $options,
    ) {}
}

class DeliveryOptionData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly float $cost,
        #[XmlAttribute]
        public readonly int $days,
    ) {}
}

class ParamData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly string $name,
        #[XmlText]
        public readonly string $value,
    ) {}
}

class VendorData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly string $code,
        public readonly string $name,
    ) {}
}
```

## Mapping rules

- **Scalars.** XML has only strings, so `int`, `float` and `bool` properties (and int-backed enums) are converted from the declared type — no `#[Cast]` needed. `bool` is true for `true` and `1`. A property with a `#[Cast]` receives the raw string.
- **Empty elements.** `<stock/>` or an empty attribute resolves to `null` when the property is nullable.
- **Repeated elements.** An `array` property collects the text of every matching element; a `#[DataCollection]` property hydrates every matching element. When there are none, a required list is empty rather than a missing-field error.
- **Nested DTOs** read a child element with their own mapping, recursively. A wrapper element such as `<delivery>` above is a DTO of its own holding the collection. `#[Flatten]` reads the nested DTO's fields from the parent element.
- **`#[Discriminator]`** works as usual. The field is read from a child element; to read it from an XML attribute, declare it as an `#[XmlAttribute]` property on the target classes.
- **Undeclared elements are skipped** without being read into memory, so a DTO that needs three fields of a fifty-field element only pays for three. This also means `#[RejectUnknownKeys]` and class-level pipes only ever see declared fields.
- **Namespaced elements** are matched by their qualified name as written in the file: `#[XmlElement('g:price')]`.
- **`#[MapInputName]` and `#[TransformKeys]`** still apply to element names, so a class already mapped for array input needs nothing extra.

## Errors

- A file that cannot be opened, or XML that is malformed or truncated, throws `DataHydrationException`. Elements read before the broken spot are still yielded; a partially read element never is.
- An element that doesn't satisfy the DTO (a missing required field, an invalid enum value) throws when iteration reaches it, like `lazyCollection()`.
