# #[XmlAttribute] / #[XmlElement] / #[XmlText]

Tell [`lazyXml()`](../features/xml.md) where in an XML element a property comes from. Without any of them a property reads the child element with its own name, so they are only needed for the three cases below.

They only affect `lazyXml()`. `from()`, `toArray()` and everything else are untouched — the same class still hydrates from a plain array under its usual keys.

## Syntax

```xml
<quantity uom="pcs" reserved="2">3</quantity>
```

```php
use StdOut\SimpleDataObjects\Attributes\XmlAttribute;
use StdOut\SimpleDataObjects\Attributes\XmlText;
use StdOut\SimpleDataObjects\BaseData;

class QuantityData extends BaseData
{
    public function __construct(
        #[XmlText]
        public readonly int $amount,       // 3
        #[XmlAttribute]
        public readonly int $reserved,     // reserved="2"
        #[XmlAttribute('uom')]
        public readonly string $unit,      // uom="pcs"
    ) {}
}
```

## #[XmlAttribute]

Reads an XML attribute of the element instead of a child element. With no argument the attribute is named like the property (after [`#[TransformKeys]`](./transform-keys.md) / [`#[MapInputName]`](./map-input-output-name.md), if present); pass a name when it differs.

## #[XmlElement]

Reads the child element(s) with the given name when it differs from the property name — typically a plural property fed by a repeated singular element:

```xml
<offer>
    <picture>https://example.com/a.jpg</picture>
    <picture>https://example.com/b.jpg</picture>
    <param name="Color">Red</param>
</offer>
```

```php
use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\XmlElement;
use StdOut\SimpleDataObjects\TypedDataCollection;

class OfferData extends BaseData
{
    public function __construct(
        #[XmlElement('picture')]
        public readonly array $pictures,
        #[DataCollection(ParamData::class)]
        #[XmlElement('param')]
        public readonly TypedDataCollection $params,
    ) {}
}
```

Namespaced elements use the qualified name as written in the file: `#[XmlElement('g:price')]`.

## #[XmlText]

Reads the element's own text. Use it on the DTO of an element that carries attributes as well as a value, like `QuantityData` above. An element with nothing but text needs no DTO at all — the parent reads it as a scalar property.

## Rules

- One per property: combining any two throws at metadata-build time.
- Values are converted from the declared type (`int`, `float`, `bool`, int-backed enums); an empty value resolves to `null` for a nullable property.
