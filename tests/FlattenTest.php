<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\AddressData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PersonData;

class FlattenTest extends TestCase
{
    private function person(): PersonData
    {
        return PersonData::from([
            'name' => 'Alice',
            'street' => '123 Main St',
            'city' => 'Kyiv',
        ]);
    }

    public function test_from_hydrates_nested_from_flat_input(): void
    {
        $person = $this->person();

        $this->assertSame('Alice', $person->name);
        $this->assertInstanceOf(AddressData::class, $person->address);
        $this->assertSame('123 Main St', $person->address->street);
        $this->assertSame('Kyiv', $person->address->city);
    }

    public function test_to_array_merges_nested_fields(): void
    {
        $arr = $this->person()->toArray();

        $this->assertSame('Alice', $arr['name']);
        $this->assertSame('123 Main St', $arr['street']);
        $this->assertSame('Kyiv', $arr['city']);
        $this->assertArrayNotHasKey('address', $arr);
    }

    public function test_round_trip_from_to_array(): void
    {
        $original = $this->person();
        $arr = $original->toArray();
        $restored = PersonData::from($arr);

        $this->assertTrue($original->equals($restored));
    }

    public function test_optional_nested_field_passes_through(): void
    {
        $person = PersonData::from([
            'name' => 'Alice',
            'street' => '123 Main St',
            'city' => 'Kyiv',
            'zip' => '01001',
        ]);

        $this->assertSame('01001', $person->address->zip);
        $arr = $person->toArray();
        $this->assertSame('01001', $arr['zip']);
    }

    public function test_with_preserves_flatten_structure(): void
    {
        $person = $this->person();
        $updated = $person->with(name: 'Bob');

        $this->assertSame('Bob', $updated->name);
        $this->assertSame('123 Main St', $updated->address->street);
        $arr = $updated->toArray();
        $this->assertArrayNotHasKey('address', $arr);
        $this->assertSame('123 Main St', $arr['street']);
    }
}
