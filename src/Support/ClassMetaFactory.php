<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use ReflectionClass;
use ReflectionParameter;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\DataCollection as DataCollectionAttribute;
use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

final class ClassMetaFactory
{
    /** @var array<string, ReflectionClass<object>> */
    private static array $reflectionCache = [];

    public static function build(string $class): ClassMeta
    {
        $reflection = self::reflect($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new ClassMeta([]);
        }

        $transformAttrs = $reflection->getAttributes(TransformKeys::class);
        $strategy = $transformAttrs !== [] ? $transformAttrs[0]->newInstance()->strategy : null;

        return new ClassMeta(array_map(
            static fn (ReflectionParameter $p): ParameterMeta => self::buildParam($p, $strategy),
            $constructor->getParameters(),
        ));
    }

    private static function buildParam(ReflectionParameter $parameter, ?string $strategy): ParameterMeta
    {
        $phpName = $parameter->getName();
        $hasDefault = $parameter->isDefaultValueAvailable();

        $mapAttrs = $parameter->getAttributes(MapPropertyName::class);
        $inputName = match (true) {
            $mapAttrs !== [] => (string) $mapAttrs[0]->newInstance()->input,
            $strategy !== null => KeyTransformer::apply($phpName, $strategy),
            default => $phpName,
        };

        $dataCollectionClass = null;
        $collectionAttrs = $parameter->getAttributes(DataCollectionAttribute::class);

        if ($collectionAttrs !== []) {
            $dataCollectionClass = $collectionAttrs[0]->newInstance()->dataClass;

            if (! class_exists($dataCollectionClass)) {
                throw DataHydrationException::classNotFound($dataCollectionClass);
            }
        }

        [$nestedDataClass, $enumClass] = TypeResolver::resolve($parameter);

        $castAttrs = $parameter->getAttributes(Cast::class);
        $rulesAttrs = $parameter->getAttributes(Rules::class);

        return new ParameterMeta(
            phpName: $phpName,
            inputName: $inputName,
            allowsNull: $parameter->allowsNull(),
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
        );
    }

    private static function reflect(string $class): ReflectionClass
    {
        return self::$reflectionCache[$class] ??= new ReflectionClass($class);
    }
}
