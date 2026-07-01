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

    /**
     * @param  list<ParameterMeta>  $parameters
     * @param  list<class-string<DataPipe>>  $pipes
     */
    public function __construct(array $parameters, array $pipes = [])
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
    }

    public static function __set_state(array $state): self
    {
        return new self($state['parameters'], $state['pipes'] ?? []);
    }
}
