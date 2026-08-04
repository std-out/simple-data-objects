<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class ParameterMeta
{
    /**
     * True when hydration needs no transformation at all — the raw input
     * value is used as-is, letting the hot path skip ValueCaster entirely.
     */
    public readonly bool $isPlain;

    /**
     * @param  list<string|int>  $inputNames  Accepted input keys, tried in order — first present wins.
     * @param  class-string|null  $nestedDataClass
     * @param  class-string|null  $enumClass
     * @param  class-string|null  $dataCollectionClass
     */
    public function __construct(
        public readonly string $phpName,
        public readonly array $inputNames,
        public readonly string $outputName,
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
        public readonly bool $viaConstructor = true,
        public readonly ?string $whenLoadedRelation = null,
        /** @var array<string, array<mixed>> */
        public readonly array $nestedRules = [],
    ) {
        $this->isPlain = $caster === null
            && $nestedDataClass === null
            && $enumClass === null
            && $dataCollectionClass === null
            && $pipes === [];
    }

    public static function __set_state(array $state): self
    {
        // Older caches only have a single 'inputName' — reuse it as both.
        $inputNames = $state['inputNames'] ?? [$state['inputName']];
        $outputName = $state['outputName'] ?? $state['inputName'] ?? $inputNames[0];

        return new self(
            phpName: $state['phpName'],
            inputNames: $inputNames,
            outputName: $outputName,
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
            viaConstructor: $state['viaConstructor'] ?? true,
            whenLoadedRelation: $state['whenLoadedRelation'] ?? null,
            nestedRules: $state['nestedRules'] ?? [],
        );
    }
}
