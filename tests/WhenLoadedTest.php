<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\WhenLoadedOrderData;
use StdOut\SimpleDataObjects\Tests\Fixtures\WhenLoadedRequiredData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class WhenLoadedTest extends TestCase
{
    private function model(array $attributes, array $loadedRelations): Model&MockObject
    {
        $model = $this->createMock(Model::class);
        $model->method('attributesToArray')->willReturn($attributes);
        $model->method('relationLoaded')->willReturnCallback(
            static fn (string $key): bool => array_key_exists($key, $loadedRelations),
        );
        $model->method('getRelation')->willReturnCallback(
            static fn (string $key): mixed => $loadedRelations[$key],
        );

        return $model;
    }

    public function test_loaded_relation_hydrates_the_mapped_property(): void
    {
        $model = $this->model(['id' => 1], ['customer' => ['name' => 'Alice']]);

        $order = WhenLoadedOrderData::fromModel($model);

        $this->assertSame('Alice', $order->client->name);
    }

    public function test_unloaded_relation_resolves_to_the_property_default(): void
    {
        $model = $this->model(['id' => 1], []);

        $order = WhenLoadedOrderData::fromModel($model);

        $this->assertNull($order->client);
        $this->assertNull($order->items);
    }

    public function test_loaded_collection_relation_hydrates_a_typed_collection(): void
    {
        $model = $this->model(['id' => 1], [
            'items' => new Collection([['sku' => 'A'], ['sku' => 'B']]),
        ]);

        $order = WhenLoadedOrderData::fromModel($model);

        $this->assertInstanceOf(TypedDataCollection::class, $order->items);
        $this->assertSame('A', $order->items[0]->sku);
        $this->assertSame('B', $order->items[1]->sku);
    }

    public function test_loaded_relation_as_a_real_eloquent_model_hydrates_via_its_own_attributes(): void
    {
        $customer = $this->createMock(Model::class);
        $customer->method('toArray')->willReturn(['name' => 'Bob']);

        $model = $this->model(['id' => 1], ['customer' => $customer]);

        $order = WhenLoadedOrderData::fromModel($model);

        $this->assertSame('Bob', $order->client->name);
    }

    public function test_missing_required_when_loaded_relation_throws(): void
    {
        $model = $this->model(['id' => 1], []);

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'owner'/");

        WhenLoadedRequiredData::fromModel($model);
    }
}
