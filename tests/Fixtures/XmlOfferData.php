<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use DateTimeImmutable;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\XmlAttribute;
use StdOut\SimpleDataObjects\Attributes\XmlElement;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;
use StdOut\SimpleDataObjects\TypedDataCollection;

class XmlOfferData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly int $id,
        #[XmlAttribute]
        public readonly bool $available,
        public readonly string $name,
        public readonly XmlPriceData $price,
        #[XmlElement('picture')]
        public readonly array $pictures,
        #[DataCollection(XmlParamData::class)]
        #[XmlElement('param')]
        public readonly TypedDataCollection $params,
        #[Flatten]
        public readonly XmlDimensionsData $dimensions,
        public readonly ?int $stock = null,
        public readonly ?string $note = null,
        public readonly ?XmlVendorData $vendor = null,
        public readonly ?XmlExtrasData $extras = null,
        public readonly ?XmlQuantityData $quantity = null,
        #[Cast(new DateTimeImmutableCast)]
        public readonly ?DateTimeImmutable $updated = null,
        public readonly XmlPriority $priority = XmlPriority::Low,
        public readonly Status $status = Status::Active,
    ) {}
}
