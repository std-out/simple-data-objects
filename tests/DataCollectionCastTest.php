<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Laravel\DataCollectionCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableItemData;
use StdOut\SimpleDataObjects\TypedDataCollection;

/**
 * Direct, isolated tests of the cast class itself — $model is unused by
 * get()/set()/compare(), so a mock with no expectations is enough. Real
 * wiring through an actual Eloquent model is covered by EloquentCastTest.
 */
class DataCollectionCastTest extends TestCase
{
    private function cast(): DataCollectionCast
    {
        return new DataCollectionCast(CastableItemData::class);
    }

    private function model(): Model
    {
        return $this->createMock(Model::class);
    }

    public function test_get_returns_null_for_null_value(): void
    {
        $this->assertNull($this->cast()->get($this->model(), 'items', null, []));
    }

    public function test_get_hydrates_from_json_string(): void
    {
        $items = $this->cast()->get($this->model(), 'items', '[{"sku":"ABC","quantity":2}]', []);

        $this->assertInstanceOf(TypedDataCollection::class, $items);
        $this->assertSame('ABC', $items[0]->sku);
    }

    public function test_get_hydrates_from_array(): void
    {
        $items = $this->cast()->get($this->model(), 'items', [['sku' => 'ABC', 'quantity' => 2]], []);

        $this->assertCount(1, $items);
    }

    public function test_get_throws_on_invalid_json(): void
    {
        $this->expectException(DataHydrationException::class);

        $this->cast()->get($this->model(), 'items', 'not-valid-json{', []);
    }

    public function test_set_returns_null_for_null_value(): void
    {
        $this->assertNull($this->cast()->set($this->model(), 'items', null, []));
    }

    public function test_set_serializes_a_hydrated_collection(): void
    {
        $json = $this->cast()->set($this->model(), 'items', CastableItemData::collection([
            ['sku' => 'ABC', 'quantity' => 2],
        ]), []);

        $this->assertJsonStringEqualsJsonString('[{"sku":"ABC","quantity":2}]', $json);
    }

    public function test_set_hydrates_a_plain_array_before_serializing(): void
    {
        $json = $this->cast()->set($this->model(), 'items', [['sku' => 'ABC', 'quantity' => 2]], []);

        $this->assertJsonStringEqualsJsonString('[{"sku":"ABC","quantity":2}]', $json);
    }

    public function test_compare_returns_true_for_identical_raw_values(): void
    {
        $raw = '[{"sku":"ABC","quantity":2}]';

        $this->assertTrue($this->cast()->compare($this->model(), 'items', $raw, $raw));
    }

    public function test_compare_returns_false_when_either_side_is_null(): void
    {
        $raw = '[{"sku":"ABC","quantity":2}]';

        $this->assertFalse($this->cast()->compare($this->model(), 'items', null, $raw));
        $this->assertFalse($this->cast()->compare($this->model(), 'items', $raw, null));
    }

    public function test_compare_decodes_before_comparing(): void
    {
        $orderedOneWay = '[{"sku":"ABC","quantity":2}]';
        $orderedTheOtherWay = '[{"quantity":2,"sku":"ABC"}]';

        $this->assertTrue($this->cast()->compare($this->model(), 'items', $orderedOneWay, $orderedTheOtherWay));
    }

    public function test_compare_detects_a_genuine_difference(): void
    {
        $a = '[{"sku":"ABC","quantity":2}]';
        $b = '[{"sku":"XYZ","quantity":9}]';

        $this->assertFalse($this->cast()->compare($this->model(), 'items', $a, $b));
    }
}
