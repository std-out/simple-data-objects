<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class XmlDimensionsData extends BaseData
{
    public function __construct(
        public readonly ?float $width = null,
        public readonly ?float $height = null,
    ) {}
}
