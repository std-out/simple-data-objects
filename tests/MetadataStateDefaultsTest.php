<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\ClassMeta;
use StdOut\SimpleDataObjects\Support\ParameterMeta;

class MetadataStateDefaultsTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function legacyParameterState(): array
    {
        return [
            'phpName' => 'name',
            'inputName' => 'full_name',
            'allowsNull' => false,
            'hasDefault' => false,
            'defaultValue' => null,
            'nestedDataClass' => null,
            'enumClass' => null,
            'dataCollectionClass' => null,
            'isHidden' => false,
            'ignoreIfNull' => false,
            'flatten' => false,
            'rules' => [],
            'caster' => null,
        ];
    }

    public function test_class_meta_from_an_old_cache_gets_safe_defaults(): void
    {
        $meta = ClassMeta::__set_state(['parameters' => []]);

        $this->assertSame([], $meta->pipes);
        $this->assertTrue($meta->hasConstructor);
        $this->assertNull($meta->discriminatorField);
        $this->assertSame([], $meta->discriminatorMap);
        $this->assertNull($meta->discriminatorFallback);
        $this->assertFalse($meta->rejectUnknownKeys);
        $this->assertNull($meta->wrapIn);
        $this->assertSame([], $meta->computed);
        $this->assertFalse($meta->inferRules);
    }

    public function test_class_meta_state_values_override_the_defaults(): void
    {
        $meta = ClassMeta::__set_state([
            'parameters' => [],
            'pipes' => ['P'],
            'hasConstructor' => false,
            'rejectUnknownKeys' => true,
            'computed' => ['total' => 'total'],
            'inferRules' => true,
        ]);

        $this->assertSame(['P'], $meta->pipes);
        $this->assertFalse($meta->hasConstructor);
        $this->assertTrue($meta->rejectUnknownKeys);
        $this->assertSame(['total' => 'total'], $meta->computed);
        $this->assertTrue($meta->inferRules);
    }

    public function test_class_meta_constructor_defaults(): void
    {
        $meta = new ClassMeta([]);

        $this->assertFalse($meta->rejectUnknownKeys);
        $this->assertFalse($meta->inferRules);
        $this->assertTrue($meta->hasConstructor);
    }

    public function test_parameter_meta_from_an_old_cache_reuses_its_single_input_name(): void
    {
        $param = ParameterMeta::__set_state(self::legacyParameterState());

        $this->assertSame(['full_name'], $param->inputNames);
        $this->assertSame('full_name', $param->outputName);
        $this->assertSame([], $param->pipes);
        $this->assertTrue($param->viaConstructor);
        $this->assertNull($param->whenLoadedRelation);
        $this->assertSame([], $param->nestedRules);
        $this->assertFalse($param->isOptional);
        $this->assertSame([], $param->hiddenExcept);
        $this->assertNull($param->phpType);
        $this->assertNull($param->xmlSource);
        $this->assertFalse($param->isSensitive);
        $this->assertTrue($param->isPlain);
    }

    public function test_parameter_meta_output_name_falls_back_to_the_first_input_name(): void
    {
        $state = ['inputNames' => ['first', 'second']] + self::legacyParameterState();
        unset($state['inputName']);

        $this->assertSame('first', ParameterMeta::__set_state($state)->outputName);
        $this->assertSame('out', ParameterMeta::__set_state(['outputName' => 'out'] + $state)->outputName);
    }

    public function test_parameter_meta_state_values_override_the_defaults(): void
    {
        $param = ParameterMeta::__set_state(['pipes' => ['P'], 'viaConstructor' => false, 'isOptional' => true] + self::legacyParameterState());

        $this->assertSame(['P'], $param->pipes);
        $this->assertFalse($param->viaConstructor);
        $this->assertTrue($param->isOptional);
        $this->assertFalse($param->isPlain);
    }

    public function test_parameter_meta_constructor_defaults(): void
    {
        $param = new ParameterMeta('a', ['a'], 'a', false, false, null, null, null, null, false, false, false, [], null);

        $this->assertTrue($param->viaConstructor);
        $this->assertFalse($param->isOptional);
        $this->assertFalse($param->isSensitive);
    }
}
