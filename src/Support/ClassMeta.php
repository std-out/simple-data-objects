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

    /** @var array<string|int, list<string|int>> */
    public readonly array $validationAliases;

    public readonly bool $cascadesNestedRules;

    /** @var array<string, true> */
    public readonly array $sensitiveFields;

    /**
     * @param  list<ParameterMeta>  $parameters
     * @param  list<class-string<DataPipe>>  $pipes
     * @param  array<int|string, class-string>  $discriminatorMap
     * @param  class-string|null  $discriminatorFallback
     * @param  array<string, string>  $computed  method name => output key
     */
    public function __construct(
        array $parameters,
        array $pipes = [],
        bool $hasConstructor = true,
        public readonly ?string $discriminatorField = null,
        public readonly array $discriminatorMap = [],
        public readonly ?string $discriminatorFallback = null,
        public readonly bool $rejectUnknownKeys = false,
        public readonly ?string $wrapIn = null,
        public readonly array $computed = [],
        public readonly bool $inferRules = false,
    ) {
        $validationRules = [];
        $validationAliases = [];
        $cascadesNestedRules = false;
        $sensitiveFields = [];

        foreach ($parameters as $meta) {
            $inputName = $meta->inputNames[0];

            if ($meta->isSensitive) {
                $sensitiveFields[$meta->phpName] = true;
            }

            if ($meta->rules !== []) {
                $validationRules[$inputName] = $meta->rules;
            }

            if (($meta->rules !== [] || $meta->nestedRules !== []) && count($meta->inputNames) > 1) {
                $validationAliases[$inputName] = array_slice($meta->inputNames, 1);
            }

            if ($inferRules && ($meta->nestedDataClass !== null || $meta->dataCollectionClass !== null)) {
                $cascadesNestedRules = true;
            }

            foreach ($meta->nestedRules as $suffix => $rules) {
                $validationRules["{$inputName}.{$suffix}"] = $rules;
            }
        }

        $this->parameters = $parameters;
        $this->validationRules = $validationRules;
        $this->validationAliases = $validationAliases;
        $this->cascadesNestedRules = $cascadesNestedRules;
        $this->sensitiveFields = $sensitiveFields;
        $this->pipes = $pipes;
        $this->hasConstructor = $hasConstructor;
        $this->hasExtraProperties = $hasConstructor && array_any($parameters, static fn (ParameterMeta $p): bool => ! $p->viaConstructor);
    }

    /**
     * Missing and unmapped discriminator values resolve to the configured
     * fallback, or null when there is none.
     *
     * @return class-string|null
     */
    public function resolveDiscriminatedClass(array $data): ?string
    {
        $value = $data[$this->discriminatorField] ?? null;

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        $target = is_int($value) || is_string($value)
            ? ($this->discriminatorMap[$value] ?? null)
            : null;

        return $target ?? $this->discriminatorFallback;
    }

    /**
     * @param  array<mixed>  $data
     * @return array{0: array<mixed>, 1: array<string, array<mixed>>}
     */
    public function validationInput(array $data): array
    {
        if ($this->validationAliases === [] && ! $this->cascadesNestedRules) {
            return [$data, $this->validationRules];
        }

        $rules = $this->validationRules;

        return [$this->prepareValidation($data, '', $rules), $rules];
    }

    /**
     * @param  array<mixed>  $data
     * @param  array<string, array<mixed>>  $rules
     * @return array<mixed>
     */
    private function prepareValidation(array $data, string $prefix, array &$rules): array
    {
        foreach ($this->validationAliases as $primary => $aliases) {
            if (array_key_exists($primary, $data)) {
                continue;
            }

            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $data)) {
                    $data[$primary] = $data[$alias];

                    break;
                }
            }
        }

        if (! $this->cascadesNestedRules) {
            return $data;
        }

        foreach ($this->parameters as $param) {
            $name = $param->inputNames[0];

            if (! isset($data[$name]) || ! is_array($data[$name])) {
                continue;
            }

            if ($param->nestedDataClass !== null) {
                $data[$name] = self::prepareNestedValidation($param->nestedDataClass, $data[$name], "{$prefix}{$name}.", $rules);
            } elseif ($param->dataCollectionClass !== null) {
                foreach ($data[$name] as $key => $item) {
                    if (is_array($item)) {
                        $data[$name][$key] = self::prepareNestedValidation($param->dataCollectionClass, $item, "{$prefix}{$name}.{$key}.", $rules);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param  array<mixed>  $data
     * @param  array<string, array<mixed>>  $rules
     * @return array<mixed>
     */
    private static function prepareNestedValidation(string $class, array $data, string $prefix, array &$rules): array
    {
        $meta = MetadataRegistry::get($class);

        if ($meta->discriminatorField !== null) {
            $target = $meta->resolveDiscriminatedClass($data);

            if ($target === null) {
                return $data;
            }

            $meta = MetadataRegistry::get($target);

            foreach ($meta->validationRules as $key => $rule) {
                $rules[$prefix.$key] = $rule;
            }
        }

        return $meta->prepareValidation($data, $prefix, $rules);
    }

    public static function __set_state(array $state): self
    {
        return new self(
            $state['parameters'],
            $state['pipes'] ?? [],
            $state['hasConstructor'] ?? true,
            $state['discriminatorField'] ?? null,
            $state['discriminatorMap'] ?? [],
            $state['discriminatorFallback'] ?? null,
            $state['rejectUnknownKeys'] ?? false,
            $state['wrapIn'] ?? null,
            $state['computed'] ?? [],
            $state['inferRules'] ?? false,
        );
    }
}
