<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Illuminate\Support\Arr;
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

    public readonly bool $pipesBeforeValidation;

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

            if ($meta->nestedDataClass !== null || $meta->dataCollectionClass !== null) {
                $cascadesNestedRules = true;
            }

            if ($meta->allowsNull || $meta->hasDefault || $meta->isOptional) {
                continue;
            }

            $nestedPrefix = $meta->flatten ? '' : "{$inputName}.";

            foreach ($meta->nestedRules as $suffix => $rules) {
                $validationRules[$nestedPrefix.$suffix] = $rules;
            }
        }

        $this->parameters = $parameters;
        $this->validationRules = $validationRules;
        $this->validationAliases = $validationAliases;
        $this->cascadesNestedRules = $cascadesNestedRules;
        $this->sensitiveFields = $sensitiveFields;
        $this->pipes = $pipes;
        $this->pipesBeforeValidation = $pipes !== [] || array_any($parameters, static fn (ParameterMeta $p): bool => $p->pipes !== [] && ! $p->flatten);
        $this->hasConstructor = $hasConstructor;
        $this->hasExtraProperties = $hasConstructor && array_any($parameters, static fn (ParameterMeta $p): bool => ! $p->viaConstructor);
    }

    /** @return array<string|int, true> */
    public function knownKeys(): array
    {
        $known = [];

        foreach ($this->parameters as $param) {
            foreach ($param->inputNames as $name) {
                $known[$name] = true;
            }
        }

        foreach ($this->computed as $key) {
            $known[$key] = true;
        }

        return $known;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    public function unknownKeys(#[\SensitiveParameter] array $data): array
    {
        return array_map(strval(...), array_keys(array_diff_key($data, $this->knownKeys())));
    }

    /**
     * Runs the class pipes, then each property's pipes on the first input
     * key present for it. #[Flatten] properties keep their pipes for
     * hydration, since they read the whole input.
     *
     * @param  class-string  $class
     * @param  array<mixed>  $data
     * @return array{0: array<mixed>, 1: array<string|int, string>} piped input and errors by key
     */
    public function runPipes(#[\SensitiveParameter] array $data, string $class): array
    {
        try {
            $data = PipelineRunner::run($data, $class, $this->pipes);
        } catch (\Throwable $e) {
            return [$data, ['$pipeline' => $e->getMessage()]];
        }

        $errors = [];

        foreach ($this->parameters as $param) {
            if ($param->pipes === [] || $param->flatten) {
                continue;
            }

            foreach ($param->inputNames as $name) {
                if (! array_key_exists($name, $data)) {
                    continue;
                }

                try {
                    $data[$name] = PipelineRunner::runOnValue($data[$name], $param->phpName, $param->pipes);
                } catch (\Throwable $e) {
                    $errors[$param->inputNames[0]] = $e->getMessage();
                }

                break;
            }
        }

        return [$data, $errors];
    }

    public function withoutPipes(): self
    {
        return self::__set_state([
            'parameters' => array_map(
                static fn (ParameterMeta $p): ParameterMeta => $p->flatten ? $p : ParameterMeta::__set_state(['pipes' => []] + get_object_vars($p)),
                $this->parameters,
            ),
            'pipes' => [],
            'rejectUnknownKeys' => false,
        ] + get_object_vars($this));
    }

    /**
     * Missing and unmapped discriminator values resolve to the configured
     * fallback, or null when there is none.
     *
     * @param  array<mixed>  $data
     * @return class-string|null
     */
    public function resolveDiscriminatedClass(#[\SensitiveParameter] array $data): ?string
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
    public function validationInput(#[\SensitiveParameter] array $data): array
    {
        if ($this->validationAliases === [] && ! $this->cascadesNestedRules) {
            return [$data, $this->validationRules];
        }

        $rules = $this->validationRules;
        $data = $this->prepareValidation($data, '', $rules);

        return [$data, self::expandCollectionWildcards($data, $rules)];
    }

    /**
     * @param  array<mixed>  $data
     * @param  array<string, array<mixed>>  $rules
     * @return array<string, array<mixed>>
     */
    private static function expandCollectionWildcards(#[\SensitiveParameter] array $data, array $rules): array
    {
        $expanded = [];

        foreach ($rules as $key => $keyRules) {
            $star = strpos($key, '.*');
            $suffix = $star === false ? '' : substr($key, $star + 2);
            $items = $star === false ? null : Arr::get($data, substr($key, 0, $star));

            if (! is_array($items)
                || ($suffix !== '' && $suffix[0] !== '.')
                || str_contains($suffix, '*')
                || ! array_all($items, static fn (mixed $item): bool => is_array($item))
                || ! array_all($keyRules, self::isIndexIndependent(...))) {
                $expanded[$key] = $keyRules;

                continue;
            }

            $prefix = substr($key, 0, $star + 1);

            foreach (array_keys($items) as $index) {
                $expanded[$prefix.$index.$suffix] = $keyRules;
            }
        }

        return $expanded;
    }

    private static function isIndexIndependent(mixed $rule): bool
    {
        return ! is_string($rule) || (! str_contains($rule, '*') && ! str_starts_with($rule, 'distinct'));
    }

    /**
     * @param  array<mixed>  $data
     * @param  array<string, array<mixed>>  $rules
     * @return array<mixed>
     */
    private function prepareValidation(#[\SensitiveParameter] array $data, string $prefix, array &$rules): array
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

            $presenceDependent = $param->allowsNull || $param->hasDefault || $param->isOptional;

            if ($param->nestedDataClass !== null) {
                if ($presenceDependent) {
                    foreach (MetadataRegistry::get($param->nestedDataClass)->validationRules as $suffix => $keyRules) {
                        $rules["{$prefix}{$name}.{$suffix}"] = $keyRules;
                    }
                }

                $data[$name] = self::prepareNestedValidation($param->nestedDataClass, $data[$name], "{$prefix}{$name}.", $rules);
            } elseif ($param->dataCollectionClass !== null) {
                if ($presenceDependent) {
                    foreach (MetadataRegistry::get($param->dataCollectionClass)->validationRules as $suffix => $keyRules) {
                        $rules["{$prefix}{$name}.*.{$suffix}"] = $keyRules;
                    }
                }

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
    private static function prepareNestedValidation(string $class, #[\SensitiveParameter] array $data, string $prefix, array &$rules): array
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

    /** @param  array<string, mixed>  $state */
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
        );
    }
}
