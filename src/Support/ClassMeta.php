<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Contracts\DataPipe;

final class ClassMeta
{
    /** @var list<ParameterMeta> */
    public readonly array $parameters;

    /** @var array<string, array<mixed>> */
    public readonly array $validationRules;

    /** @var list<class-string<DataPipe>> */
    public readonly array $pipes;

    public readonly bool $hasConstructor;

    /**
     * True for a class that has a constructor AND additional plain properties
     * declared outside it (hydrated via post-construction assignment, not
     * constructor injection). False for both pure-constructor classes and
     * pure constructor-less classes — those don't mix sourcing strategies.
     */
    public readonly bool $hasExtraProperties;

    /**
     * @param  list<ParameterMeta>  $parameters
     * @param  list<class-string<DataPipe>>  $pipes
     */
    public function __construct(array $parameters, array $pipes = [], bool $hasConstructor = true)
    {
        $validationRules = [];

        foreach ($parameters as $meta) {
            if ($meta->rules !== []) {
                $validationRules[$meta->inputName] = $meta->rules;
            }
        }

        $this->parameters = $parameters;
        $this->validationRules = $validationRules;
        $this->pipes = $pipes;
        $this->hasConstructor = $hasConstructor;
        $this->hasExtraProperties = $hasConstructor && array_any($parameters, static fn (ParameterMeta $p): bool => ! $p->viaConstructor);
    }

    public static function __set_state(array $state): self
    {
        return new self($state['parameters'], $state['pipes'] ?? [], $state['hasConstructor'] ?? true);
    }
}
