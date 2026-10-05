<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\XmlAttribute;
use StdOut\SimpleDataObjects\Attributes\XmlText;
use StdOut\SimpleDataObjects\BaseData;

class XmlQuantityData extends BaseData
{
    public function __construct(
        #[XmlText]
        public readonly int $amount,
        #[XmlAttribute('uom')]
        public readonly string $unit,
    ) {}
}
