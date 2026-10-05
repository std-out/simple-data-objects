<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\XmlAttribute;

class XmlCircleData extends XmlShapeData
{
    public function __construct(
        #[XmlAttribute]
        public readonly string $kind,
        public readonly float $radius,
    ) {}
}
