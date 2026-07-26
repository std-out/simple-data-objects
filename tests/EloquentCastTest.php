<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableAddressData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableBankPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableCardPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableItemData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CastableOrderModel;
use StdOut\SimpleDataObjects\TypedDataCollection;

/**
 * End-to-end against a real Eloquent model backed by an in-memory SQLite
 * database — proves IsEloquentCastable/AsDataCollection integrate with
 * Eloquent's actual cast-resolution pipeline, not just our own classes.
 */
class EloquentCastTest extends TestCase
{
    private static Capsule $capsule;

    public static function setUpBeforeClass(): void
    {
        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('castable_order_models', function (Blueprint $table): void {
            $table->id();
            $table->string('address')->nullable();
            $table->string('items')->nullable();
            $table->string('payment')->nullable();
        });

        self::$capsule = $capsule;
    }

    protected function tearDown(): void
    {
        self::$capsule->getConnection()->table('castable_order_models')->truncate();
    }

    public function test_single_data_object_round_trips_through_save_and_fresh_load(): void
    {
        $model = CastableOrderModel::create([
            'address' => new CastableAddressData('123 Main St', 'Kyiv'),
        ]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertInstanceOf(CastableAddressData::class, $reloaded->address);
        $this->assertSame('123 Main St', $reloaded->address->street);
        $this->assertSame('Kyiv', $reloaded->address->city);
    }

    public function test_single_data_object_accepts_a_plain_array_on_set(): void
    {
        $model = CastableOrderModel::create([
            'address' => ['street' => '1 Infinite Loop', 'city' => 'Cupertino'],
        ]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertInstanceOf(CastableAddressData::class, $reloaded->address);
        $this->assertSame('1 Infinite Loop', $reloaded->address->street);
    }

    public function test_null_column_stays_null(): void
    {
        $model = CastableOrderModel::create(['address' => null]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertNull($reloaded->address);
    }

    public function test_invalid_json_in_the_column_throws_on_access(): void
    {
        $model = CastableOrderModel::create(['address' => new CastableAddressData('St', 'City')]);

        self::$capsule->getConnection()
            ->table('castable_order_models')
            ->where('id', $model->id)
            ->update(['address' => 'not-valid-json{']);

        $this->expectException(DataHydrationException::class);

        CastableOrderModel::find($model->id)->address;
    }

    public function test_data_collection_round_trips_through_save_and_fresh_load(): void
    {
        $model = CastableOrderModel::create([
            'items' => [
                ['sku' => 'ABC', 'quantity' => 2],
                ['sku' => 'XYZ', 'quantity' => 5],
            ],
        ]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertInstanceOf(TypedDataCollection::class, $reloaded->items);
        $this->assertCount(2, $reloaded->items);
        $this->assertInstanceOf(CastableItemData::class, $reloaded->items[0]);
        $this->assertSame('ABC', $reloaded->items[0]->sku);
        $this->assertSame(5, $reloaded->items[1]->quantity);
    }

    public function test_data_collection_accepts_an_already_hydrated_collection_on_set(): void
    {
        $model = new CastableOrderModel;
        $model->items = CastableItemData::collection([
            ['sku' => 'ABC', 'quantity' => 1],
        ]);
        $model->save();

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertSame('ABC', $reloaded->items[0]->sku);
    }

    public function test_null_collection_column_stays_null(): void
    {
        $model = CastableOrderModel::create(['items' => null]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertNull($reloaded->items);
    }

    public function test_invalid_json_in_the_collection_column_throws_on_access(): void
    {
        $model = CastableOrderModel::create(['items' => [['sku' => 'ABC', 'quantity' => 1]]]);

        self::$capsule->getConnection()
            ->table('castable_order_models')
            ->where('id', $model->id)
            ->update(['items' => 'not-valid-json{']);

        $this->expectException(DataHydrationException::class);

        CastableOrderModel::find($model->id)->items;
    }

    public function test_dirty_check_compares_decoded_values_not_raw_json_bytes(): void
    {
        $model = CastableOrderModel::create([
            'address' => new CastableAddressData('123 Main St', 'Kyiv'),
        ])->fresh();

        // Simulate a differently-key-ordered (but semantically identical)
        // JSON string already in storage, e.g. written by another system.
        // Without DataObjectCast::compare(), Eloquent's dirty-check falls
        // back to raw byte comparison and this would report dirty.
        self::$capsule->getConnection()
            ->table('castable_order_models')
            ->where('id', $model->id)
            ->update(['address' => '{"city":"Kyiv","street":"123 Main St"}']);

        $reloaded = CastableOrderModel::find($model->id);
        $reloaded->address = CastableAddressData::from(['street' => '123 Main St', 'city' => 'Kyiv']);

        $this->assertFalse($reloaded->isDirty('address'));
    }

    public function test_dirty_check_detects_a_genuine_change(): void
    {
        $model = CastableOrderModel::create([
            'address' => new CastableAddressData('123 Main St', 'Kyiv'),
        ])->fresh();

        $model->address = new CastableAddressData('456 Other St', 'Lviv');

        $this->assertTrue($model->isDirty('address'));
    }

    public function test_collection_dirty_check_compares_decoded_values(): void
    {
        $model = CastableOrderModel::create([
            'items' => [['sku' => 'ABC', 'quantity' => 2]],
        ])->fresh();

        self::$capsule->getConnection()
            ->table('castable_order_models')
            ->where('id', $model->id)
            ->update(['items' => '[{"quantity":2,"sku":"ABC"}]']);

        $reloaded = CastableOrderModel::find($model->id);
        $reloaded->items = CastableItemData::collection([['sku' => 'ABC', 'quantity' => 2]]);

        $this->assertFalse($reloaded->isDirty('items'));
    }

    public function test_cast_target_can_be_an_abstract_discriminator_class(): void
    {
        $model = CastableOrderModel::create([
            'payment' => ['type' => 'card', 'last4' => '4242'],
        ]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertInstanceOf(CastableCardPaymentData::class, $reloaded->payment);
        $this->assertSame('4242', $reloaded->payment->last4);
    }

    public function test_discriminator_cast_target_dispatches_per_subclass_on_save_too(): void
    {
        $model = CastableOrderModel::create([
            'payment' => ['type' => 'bank', 'iban' => 'UA903052992990004149123456789'],
        ]);

        $reloaded = CastableOrderModel::find($model->id)->fresh();

        $this->assertInstanceOf(CastableBankPaymentData::class, $reloaded->payment);
        $this->assertSame('UA903052992990004149123456789', $reloaded->payment->iban);
    }
}
