<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Laravel\DataObjectCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableAddressData;

/**
 * Direct, isolated tests of the cast class itself — $model is unused by
 * get()/set()/compare(), so a mock with no expectations is enough. Real
 * wiring through an actual Eloquent model is covered by EloquentCastTest.
 */
class DataObjectCastTest extends TestCase
{
    private function cast(): DataObjectCast
    {
        return new DataObjectCast(CastableAddressData::class);
    }

    private function model(): Model
    {
        return $this->createMock(Model::class);
    }

    public function test_get_returns_null_for_null_value(): void
    {
        $this->assertNull($this->cast()->get($this->model(), 'address', null, []));
    }

    public function test_get_hydrates_from_json_string(): void
    {
        $address = $this->cast()->get($this->model(), 'address', '{"street":"1 Main St","city":"Kyiv"}', []);

        $this->assertInstanceOf(CastableAddressData::class, $address);
        $this->assertSame('1 Main St', $address->street);
    }

    public function test_get_hydrates_from_array(): void
    {
        $address = $this->cast()->get($this->model(), 'address', ['street' => '1 Main St', 'city' => 'Kyiv'], []);

        $this->assertSame('Kyiv', $address->city);
    }

    public function test_set_returns_null_for_null_value(): void
    {
        $this->assertNull($this->cast()->set($this->model(), 'address', null, []));
    }

    public function test_set_serializes_an_instance_to_json(): void
    {
        $json = $this->cast()->set($this->model(), 'address', new CastableAddressData('1 Main St', 'Kyiv'), []);

        $this->assertJsonStringEqualsJsonString('{"street":"1 Main St","city":"Kyiv"}', $json);
    }

    public function test_set_hydrates_an_array_before_serializing(): void
    {
        $json = $this->cast()->set($this->model(), 'address', ['street' => '1 Main St', 'city' => 'Kyiv'], []);

        $this->assertJsonStringEqualsJsonString('{"street":"1 Main St","city":"Kyiv"}', $json);
    }

    public function test_compare_returns_true_for_identical_raw_values(): void
    {
        $raw = '{"street":"1 Main St","city":"Kyiv"}';

        $this->assertTrue($this->cast()->compare($this->model(), 'address', $raw, $raw));
    }

    public function test_compare_returns_false_when_either_side_is_null(): void
    {
        $raw = '{"street":"1 Main St","city":"Kyiv"}';

        $this->assertFalse($this->cast()->compare($this->model(), 'address', null, $raw));
        $this->assertFalse($this->cast()->compare($this->model(), 'address', $raw, null));
    }

    public function test_compare_decodes_before_comparing(): void
    {
        $orderedOneWay = '{"street":"1 Main St","city":"Kyiv"}';
        $orderedTheOtherWay = '{"city":"Kyiv","street":"1 Main St"}';

        $this->assertTrue($this->cast()->compare($this->model(), 'address', $orderedOneWay, $orderedTheOtherWay));
    }

    public function test_compare_detects_a_genuine_difference(): void
    {
        $a = '{"street":"1 Main St","city":"Kyiv"}';
        $b = '{"street":"2 Other St","city":"Lviv"}';

        $this->assertFalse($this->cast()->compare($this->model(), 'address', $a, $b));
    }
}
