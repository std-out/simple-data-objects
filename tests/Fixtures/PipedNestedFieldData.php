<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class PipedNestedFieldData extends BaseData
{
    public function __construct(
        #[Pipe(NoopValuePipe::class)]
        public readonly AddressData $address,
        #[Pipe(NoopValuePipe::class)]
        #[DataCollection(UserData::class)]
        public readonly TypedDataCollection $members,
    ) {}
}
