<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\DataCollection as DataCollectionAttribute;
use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;
use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\Attributes\WhenLoaded;
use StdOut\SimpleDataObjects\Contracts\DataObject;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

final class ClassMetaFactory
{
    public static function build(string $class): ClassMeta
    {
        return RuleInferrer::guarding($class, static fn (): ClassMeta => self::doBuild($class));
    }

    private static function doBuild(string $class): ClassMeta
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $inferRules = $reflection->getAttributes(InferRules::class) !== [];

        $transformAttrs = $reflection->getAttributes(TransformKeys::class);
        $strategy = $transformAttrs !== [] ? $transformAttrs[0]->newInstance()->strategy : null;

        $pipeAttrs = $reflection->getAttributes(Pipe::class);
        $pipes = $pipeAttrs !== [] ? $pipeAttrs[0]->newInstance()->pipes : [];

        $discriminator = self::discriminator($reflection);

        if ($discriminator !== null && $pipes !== []) {
            throw new \InvalidArgumentException(
                "{$class}: #[Discriminator] and class-level #[Pipe] cannot be combined — declare pipes on the concrete subclasses instead.",
            );
        }

        $rejectUnknownKeys = $reflection->getAttributes(RejectUnknownKeys::class) !== [];

        if ($rejectUnknownKeys && $discriminator !== null) {
            throw new \InvalidArgumentException(
                "{$class}: #[RejectUnknownKeys] and #[Discriminator] cannot be combined — declare it on the concrete subclasses instead.",
            );
        }

        // No constructor: fall back to the class's own public typed properties
        // (plain property-declaration DTOs), hydrated via post-construction
        // assignment instead of constructor injection.
        if ($constructor === null) {
            $members = self::publicTypedProperties($reflection, []);
            $params = array_map(
                static fn (ReflectionProperty $p): ParameterMeta => self::buildParam($p, $strategy, $inferRules, viaConstructor: false),
                $members,
            );

            self::assertNoFlattenWithRejectUnknownKeys($class, $params, $rejectUnknownKeys);

            return new ClassMeta(
                $params,
                $pipes,
                hasConstructor: false,
                discriminatorField: $discriminator?->field,
                discriminatorMap: $discriminator?->map ?? [],
                discriminatorFallback: $discriminator?->fallback,
                rejectUnknownKeys: $rejectUnknownKeys,
            );
        }

        $ctorParams = array_map(
            static fn (ReflectionParameter $p): ParameterMeta => self::buildParam($p, $strategy, $inferRules, viaConstructor: true),
            $constructor->getParameters(),
        );

        // Hybrid: a promoted property already surfaces as a constructor
        // parameter above, so exclude those names here — a non-promoted
        // parameter creates no property at all and can't collide.
        $ctorNames = array_map(static fn (ReflectionParameter $p): string => $p->getName(), $constructor->getParameters());
        $extraProps = self::publicTypedProperties($reflection, $ctorNames);

        $extraParams = array_map(
            static fn (ReflectionProperty $p): ParameterMeta => self::buildParam($p, $strategy, $inferRules, viaConstructor: false),
            $extraProps,
        );

        $allParams = array_merge($ctorParams, $extraParams);

        self::assertNoFlattenWithRejectUnknownKeys($class, $allParams, $rejectUnknownKeys);

        return new ClassMeta(
            $allParams,
            $pipes,
            discriminatorField: $discriminator?->field,
            discriminatorMap: $discriminator?->map ?? [],
            discriminatorFallback: $discriminator?->fallback,
            rejectUnknownKeys: $rejectUnknownKeys,
        );
    }

    /** @param list<ParameterMeta> $params */
    private static function assertNoFlattenWithRejectUnknownKeys(string $class, array $params, bool $rejectUnknownKeys): void
    {
        if ($rejectUnknownKeys && array_any($params, static fn (ParameterMeta $p): bool => $p->flatten)) {
            throw new \InvalidArgumentException(
                "{$class}: #[RejectUnknownKeys] and #[Flatten] cannot be combined — a flattened nested DTO consumes keys the parent doesn't know about.",
            );
        }
    }

    /**
     * Reads and validates #[Discriminator] so every configuration error is
     * caught at metadata-build time, never on a live hydration.
     *
     * @param  ReflectionClass<object>  $reflection
     */
    private static function discriminator(ReflectionClass $reflection): ?Discriminator
    {
        $attrs = $reflection->getAttributes(Discriminator::class);

        if ($attrs === []) {
            return null;
        }

        $class = $reflection->getName();

        if (! $reflection->isAbstract()) {
            throw new \InvalidArgumentException(
                "{$class}: #[Discriminator] requires an abstract class — a concrete class in the map would recurse into itself.",
            );
        }

        /** @var Discriminator $discriminator */
        $discriminator = $attrs[0]->newInstance();

        if ($discriminator->map === []) {
            throw new \InvalidArgumentException("{$class}: #[Discriminator] map cannot be empty.");
        }

        foreach ($discriminator->map as $target) {
            self::validateDiscriminatorTarget($class, $target);
        }

        if ($discriminator->fallback !== null) {
            self::validateDiscriminatorTarget($class, $discriminator->fallback);
        }

        return $discriminator;
    }

    private static function validateDiscriminatorTarget(string $class, mixed $target): void
    {
        if (! is_string($target) || ! class_exists($target)) {
            $given = is_string($target) ? "'{$target}'" : get_debug_type($target);

            throw new \InvalidArgumentException("{$class}: #[Discriminator] target {$given} is not an existing class.");
        }

        if (! is_subclass_of($target, $class)) {
            throw new \InvalidArgumentException("{$class}: #[Discriminator] target '{$target}' must extend {$class}.");
        }

        // An abstract target can only hydrate by dispatching further down —
        // without its own #[Discriminator] it would fail on every input.
        $targetReflection = new ReflectionClass($target);

        if ($targetReflection->isAbstract() && $targetReflection->getAttributes(Discriminator::class) === []) {
            throw new \InvalidArgumentException(
                "{$class}: #[Discriminator] target '{$target}' is abstract and must declare its own #[Discriminator].",
            );
        }
    }

    /**
     * @param  list<string>  $excludeNames
     * @return list<ReflectionProperty>
     */
    private static function publicTypedProperties(ReflectionClass $reflection, array $excludeNames): array
    {
        return array_values(array_filter(
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn (ReflectionProperty $p): bool => ! $p->isStatic()
                && $p->getType() !== null
                && ! in_array($p->getName(), $excludeNames, true),
        ));
    }

    private static function buildParam(ReflectionParameter|ReflectionProperty $parameter, ?string $strategy, bool $inferRules, bool $viaConstructor): ParameterMeta
    {
        $phpName = $parameter->getName();
        $hasDefault = $parameter instanceof ReflectionParameter
            ? $parameter->isDefaultValueAvailable()
            : $parameter->hasDefaultValue();

        $mapAttrs = $parameter->getAttributes(MapPropertyName::class);
        $inputName = match (true) {
            $mapAttrs !== [] => (string) $mapAttrs[0]->newInstance()->input,
            $strategy !== null => KeyTransformer::apply($phpName, $strategy),
            default => $phpName,
        };

        $dataCollectionClass = null;
        $collectionAttrs = $parameter->getAttributes(DataCollectionAttribute::class);
        $castAttrs = $parameter->getAttributes(Cast::class);
        $flattenAttrs = $parameter->getAttributes(Flatten::class);

        if ($collectionAttrs !== []) {
            $dataCollectionClass = $collectionAttrs[0]->newInstance()->dataClass;

            if (! class_exists($dataCollectionClass)) {
                throw DataHydrationException::classNotFound($dataCollectionClass);
            }

            if (! is_subclass_of($dataCollectionClass, DataObject::class)) {
                throw DataHydrationException::notADataObject($dataCollectionClass);
            }

            if ($castAttrs !== []) {
                throw new \InvalidArgumentException(
                    "Parameter \"{$phpName}\": #[DataCollection] and #[Cast] cannot be combined.",
                );
            }

            if ($flattenAttrs !== []) {
                throw new \InvalidArgumentException(
                    "Parameter \"{$phpName}\": #[DataCollection] and #[Flatten] cannot be combined.",
                );
            }
        }

        if ($flattenAttrs !== [] && $castAttrs !== []) {
            throw new \InvalidArgumentException(
                "Parameter \"{$phpName}\": #[Flatten] and #[Cast] cannot be combined.",
            );
        }

        [$nestedDataClass, $enumClass] = TypeResolver::resolve($parameter);

        if ($flattenAttrs !== [] && $nestedDataClass === null) {
            throw new \InvalidArgumentException(
                "Parameter \"{$phpName}\": #[Flatten] requires a nested BaseData type.",
            );
        }

        $rulesAttrs = $parameter->getAttributes(Rules::class);
        $explicitRules = $rulesAttrs !== [] ? $rulesAttrs[0]->newInstance() : null;
        $pipeAttrs = $parameter->getAttributes(Pipe::class);
        $paramPipes = $pipeAttrs !== [] ? $pipeAttrs[0]->newInstance()->pipes : [];

        $whenLoadedAttrs = $parameter->getAttributes(WhenLoaded::class);

        $allowsNull = $parameter instanceof ReflectionParameter
            ? $parameter->allowsNull()
            : ($parameter->getType()?->allowsNull() ?? true);

        $rules = match (true) {
            $explicitRules !== null && ! $explicitRules->merge => $explicitRules->rules,
            $inferRules => [
                ...RuleInferrer::forParameter($parameter, $allowsNull, $nestedDataClass, $enumClass, $dataCollectionClass),
                ...$explicitRules?->rules ?? [],
            ],
            default => $explicitRules?->rules ?? [],
        };

        return new ParameterMeta(
            phpName: $phpName,
            inputName: $inputName,
            allowsNull: $allowsNull,
            hasDefault: $hasDefault,
            defaultValue: $hasDefault ? $parameter->getDefaultValue() : null,
            nestedDataClass: $nestedDataClass,
            enumClass: $enumClass,
            dataCollectionClass: $dataCollectionClass,
            isHidden: $parameter->getAttributes(Hidden::class) !== [],
            ignoreIfNull: $parameter->getAttributes(IgnoreIfNull::class) !== [],
            flatten: $parameter->getAttributes(Flatten::class) !== [],
            rules: $rules,
            caster: $castAttrs !== [] ? $castAttrs[0]->newInstance()->caster : null,
            pipes: $paramPipes,
            viaConstructor: $viaConstructor,
            whenLoadedRelation: $whenLoadedAttrs !== [] ? $whenLoadedAttrs[0]->newInstance()->relation : null,
            nestedRules: $inferRules ? RuleInferrer::cascade($nestedDataClass, $dataCollectionClass) : [],
        );
    }
}
