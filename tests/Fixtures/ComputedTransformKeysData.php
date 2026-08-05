<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\BaseData;

#[TransformKeys(TransformKeys::SNAKE_CASE)]
class ComputedTransformKeysData extends BaseData
{
    public function __construct(
        public readonly string $firstName,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return $this->firstName;
    }
}
