<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedCustomKeyData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedNameData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedNestedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedNoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedRequiredParamData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedStaticMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ComputedTransformKeysData;
use StdOut\SimpleDataObjects\Tests\Fixtures\StrictComputedData;

class ComputedTest extends TestCase
{
    public function test_computed_method_is_included_in_to_array_under_its_own_name(): void
    {
        $data = new ComputedNameData('Ada', 'Lovelace');

        $this->assertSame([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'fullName' => 'Ada Lovelace',
        ], $data->toArray());
    }

    public function test_computed_key_can_be_overridden(): void
    {
        $data = new ComputedCustomKeyData('Ada', 'Lovelace');

        $array = $data->toArray();

        $this->assertSame('Ada Lovelace', $array['full_name']);
        $this->assertArrayNotHasKey('fullName', $array);
    }

    public function test_hydration_ignores_a_computed_key_present_in_the_input(): void
    {
        $data = ComputedNameData::from([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'fullName' => 'whatever the caller sent',
        ]);

        $this->assertSame('Ada Lovelace', $data->fullName());
    }

    public function test_roundtrip_from_to_array(): void
    {
        $original = new ComputedNameData('Ada', 'Lovelace');
        $restored = ComputedNameData::from($original->toArray());

        $this->assertTrue($original->equals($restored));
    }

    public function test_works_on_constructor_less_classes(): void
    {
        $data = ComputedNoConstructorData::from(['firstName' => 'Ada', 'lastName' => 'Lovelace']);

        $this->assertSame('Ada Lovelace', $data->toArray()['fullName']);
    }

    public function test_default_key_follows_the_class_level_transform_keys_strategy(): void
    {
        $data = new ComputedTransformKeysData('Ada');

        $this->assertSame(['first_name' => 'Ada', 'full_name' => 'Ada'], $data->toArray());
    }

    public function test_reject_unknown_keys_treats_the_computed_key_as_known(): void
    {
        $original = new StrictComputedData('Ada', 'Lovelace');

        $restored = StrictComputedData::from($original->toArray());

        $this->assertTrue($original->equals($restored));
    }

    public function test_computed_method_requiring_parameters_throws_at_metadata_build_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/#\[Computed\] methods cannot require parameters/');

        ComputedRequiredParamData::from(['name' => 'Ada']);
    }

    public function test_static_methods_are_not_treated_as_computed(): void
    {
        $data = ComputedStaticMethodData::from(['name' => 'Ada']);

        $this->assertArrayNotHasKey('staticComputed', $data->toArray());
    }

    public function test_computed_value_is_normalized_like_any_other_field(): void
    {
        $data = new ComputedNestedData('12 Analytical Engine Ave', 'London');

        $this->assertSame([
            'street' => '12 Analytical Engine Ave',
            'city' => 'London',
            'address' => ['street' => '12 Analytical Engine Ave', 'city' => 'London', 'zip' => null],
        ], $data->toArray());
    }
}
