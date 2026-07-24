<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\DataCollection as DataCollectionAttribute;
use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\Contracts\DataObject;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

final class ClassMetaFactory
{
    public static function build(string $class): ClassMeta
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        $transformAttrs = $reflection->getAttributes(TransformKeys::class);
        $strategy = $transformAttrs !== [] ? $transformAttrs[0]->newInstance()->strategy : null;

        $pipeAttrs = $reflection->getAttributes(Pipe::class);
        $pipes = $pipeAttrs !== [] ? $pipeAttrs[0]->newInstance()->pipes : [];

        // No constructor: fall back to the class's own public typed properties
        // (plain property-declaration DTOs), hydrated via post-construction
        // assignment instead of constructor injection.
        if ($constructor === null) {
            $members = self::publicTypedProperties($reflection, []);

            return new ClassMeta(
                array_map(
                    static fn (ReflectionProperty $p): ParameterMeta => self::buildParam($p, $strategy, viaConstructor: false),
                    $members,
                ),
                $pipes,
                hasConstructor: false,
            );
        }

        $ctorParams = array_map(
            static fn (ReflectionParameter $p): ParameterMeta => self::buildParam($p, $strategy, viaConstructor: true),
            $constructor->getParameters(),
        );

        // Hybrid: a promoted property already surfaces as a constructor
        // parameter above, so exclude those names here — a non-promoted
        // parameter creates no property at all and can't collide.
        $ctorNames = array_map(static fn (ReflectionParameter $p): string => $p->getName(), $constructor->getParameters());
        $extraProps = self::publicTypedProperties($reflection, $ctorNames);

        $extraParams = array_map(
            static fn (ReflectionProperty $p): ParameterMeta => self::buildParam($p, $strategy, viaConstructor: false),
            $extraProps,
        );

        return new ClassMeta(array_merge($ctorParams, $extraParams), $pipes);
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

    private static function buildParam(ReflectionParameter|ReflectionProperty $parameter, ?string $strategy, bool $viaConstructor): ParameterMeta
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
        $pipeAttrs = $parameter->getAttributes(Pipe::class);
        $paramPipes = $pipeAttrs !== [] ? $pipeAttrs[0]->newInstance()->pipes : [];

        return new ParameterMeta(
            phpName: $phpName,
            inputName: $inputName,
            allowsNull: $parameter instanceof ReflectionParameter
                ? $parameter->allowsNull()
                : ($parameter->getType()?->allowsNull() ?? true),
            hasDefault: $hasDefault,
            defaultValue: $hasDefault ? $parameter->getDefaultValue() : null,
            nestedDataClass: $nestedDataClass,
            enumClass: $enumClass,
            dataCollectionClass: $dataCollectionClass,
            isHidden: $parameter->getAttributes(Hidden::class) !== [],
            ignoreIfNull: $parameter->getAttributes(IgnoreIfNull::class) !== [],
            flatten: $parameter->getAttributes(Flatten::class) !== [],
            rules: $rulesAttrs !== [] ? $rulesAttrs[0]->newInstance()->rules : [],
            caster: $castAttrs !== [] ? $castAttrs[0]->newInstance()->caster : null,
            pipes: $paramPipes,
            viaConstructor: $viaConstructor,
        );
    }
}
