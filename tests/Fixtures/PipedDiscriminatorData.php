<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;

#[Pipe(TrimStringsPipe::class)]
#[Discriminator(field: 'type', map: ['child' => PipedChildData::class])]
abstract class PipedDiscriminatorData extends BaseData {}
