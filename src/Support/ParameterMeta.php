<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class ParameterMeta
{
    public function __construct(
        public readonly string $phpName,
        public readonly string $inputName,
        public readonly bool $allowsNull,
        public readonly bool $hasDefault,
        public readonly mixed $defaultValue,
        public readonly ?string $nestedDataClass,
        public readonly ?string $enumClass,
        public readonly ?string $dataCollectionClass,
        public readonly bool $isHidden,
        public readonly bool $ignoreIfNull,
        public readonly ?CastsValue $caster,
    ) {}
}
