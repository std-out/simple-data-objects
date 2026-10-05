<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\XmlAttribute;
use StdOut\SimpleDataObjects\Attributes\XmlText;
use StdOut\SimpleDataObjects\BaseData;

class XmlParamData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly string $name,
        #[XmlText]
        public readonly string $value,
        #[XmlAttribute]
        public readonly ?int $weight = null,
    ) {}
}
