<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\XmlAttribute;
use StdOut\SimpleDataObjects\BaseData;

class XmlVendorData extends BaseData
{
    public function __construct(
        #[XmlAttribute]
        public readonly string $code,
        public readonly ?string $name = null,
    ) {}
}
