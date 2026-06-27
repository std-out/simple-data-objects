<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class ClassMeta
{
    /** @var list<ParameterMeta> */
    public readonly array $parameters;

    /** @var array<string, true> */
    public readonly array $hidden;

    /** @var array<string, true> */
    public readonly array $ignoreIfNull;

    /** @var array<string, CastsValue> */
    public readonly array $casters;

    /** @var array<string, array<mixed>> */
    public readonly array $validationRules;

    /** @param list<ParameterMeta> $parameters */
    public function __construct(array $parameters)
    {
        $hidden = [];
        $ignoreIfNull = [];
        $casters = [];
        $validationRules = [];

        foreach ($parameters as $meta) {
            if ($meta->isHidden) {
                $hidden[$meta->phpName] = true;
            }

            if ($meta->ignoreIfNull) {
                $ignoreIfNull[$meta->phpName] = true;
            }

            if ($meta->caster !== null) {
                $casters[$meta->phpName] = $meta->caster;
            }

            if ($meta->rules !== []) {
                $validationRules[$meta->inputName] = $meta->rules;
            }
        }

        $this->parameters = $parameters;
        $this->hidden = $hidden;
        $this->ignoreIfNull = $ignoreIfNull;
        $this->casters = $casters;
        $this->validationRules = $validationRules;
    }
}
