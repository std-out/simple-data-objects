<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\XmlElement;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class XmlExtrasData extends BaseData
{
    public function __construct(
        #[DataCollection(XmlParamData::class)]
        #[XmlElement('param')]
        public readonly TypedDataCollection $params,
    ) {}
}
