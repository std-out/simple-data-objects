<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class HybridData extends BaseData
{
    public function __construct(
        public readonly string $id,
        public readonly string $status = 'new',
    ) {}

    public ?string $note = null;

    public readonly string $extraId;

    #[MapPropertyName('extra_label')]
    public string $extraLabel;
}
