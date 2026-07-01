<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class ParameterMeta
{
    /**
     * @param  class-string|null  $nestedDataClass
     * @param  class-string|null  $enumClass
     * @param  class-string|null  $dataCollectionClass
     */
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
        public readonly bool $flatten,
        public readonly array $rules,
        public readonly ?CastsValue $caster,
        /** @var list<class-string<ValuePipe>> */
        public readonly array $pipes = [],
    ) {}

    public static function __set_state(array $state): self
    {
        return new self(
            phpName: $state['phpName'],
            inputName: $state['inputName'],
            allowsNull: $state['allowsNull'],
            hasDefault: $state['hasDefault'],
            defaultValue: $state['defaultValue'],
            nestedDataClass: $state['nestedDataClass'],
            enumClass: $state['enumClass'],
            dataCollectionClass: $state['dataCollectionClass'],
            isHidden: $state['isHidden'],
            ignoreIfNull: $state['ignoreIfNull'],
            flatten: $state['flatten'],
            rules: $state['rules'],
            caster: $state['caster'],
            pipes: $state['pipes'] ?? [],
        );
    }
}
