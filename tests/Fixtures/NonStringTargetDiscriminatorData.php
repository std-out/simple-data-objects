<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\BaseData;

// Deliberately not a class-string — exercises the non-string-target branch
// of ClassMetaFactory::validateDiscriminatorTarget().
/** @noinspection PhpParamsInspection */
#[Discriminator(field: 'type', map: ['x' => 123])]
abstract class NonStringTargetDiscriminatorData extends BaseData {}
