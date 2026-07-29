<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InjectableUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Laravel\TestCase;

/**
 * End-to-end: a real routed HTTP request through a booted Laravel app,
 * proving the beforeResolving(BaseData::class, ...) hook registered by
 * SimpleDataObjectsServiceProvider actually lets a controller/closure
 * type-hint a BaseData subclass and receive it auto-hydrated + validated —
 * no FormRequest involved.
 */
class ControllerInjectionTest extends TestCase
{
    public function test_type_hinted_data_object_is_auto_hydrated_from_request(): void
    {
        Route::post('/injectable', function (InjectableUserData $data) {
            return response()->json($data->toArray());
        });

        $this->postJson('/injectable', ['name' => 'Alice', 'email' => 'alice@example.com'])
            ->assertOk()
            ->assertJson(['name' => 'Alice', 'email' => 'alice@example.com']);
    }

    public function test_invalid_input_surfaces_as_a_422(): void
    {
        Route::post('/injectable', function (InjectableUserData $data) {
            return response()->json($data->toArray());
        });

        $this->postJson('/injectable', ['name' => '', 'email' => 'not-an-email'])
            ->assertStatus(422);
    }

    public function test_class_without_has_laravel_integration_is_not_auto_bound(): void
    {
        // OrderData has required scalar/enum constructor params and no
        // fromRequest() — the container must fail to autowire it normally,
        // proving the injection hook skipped it rather than mis-hydrating.
        Route::post('/order', function (OrderData $data) {
            return response()->json($data->toArray());
        });

        $this->postJson('/order', ['id' => 1, 'status' => 'active'])
            ->assertStatus(500);
    }

    public function test_inject_from_request_config_flag_disables_injection(): void
    {
        config(['simple-data-objects.inject_from_request' => false]);

        Route::post('/injectable', function (InjectableUserData $data) {
            return response()->json($data->toArray());
        });

        $this->postJson('/injectable', ['name' => 'Alice', 'email' => 'alice@example.com'])
            ->assertStatus(500);
    }

    public function test_explicit_container_binding_is_not_overridden(): void
    {
        $this->app->bind(InjectableUserData::class, fn () => InjectableUserData::from([
            'name' => 'Bound',
            'email' => 'bound@example.com',
        ]));

        Route::post('/injectable', function (InjectableUserData $data) {
            return response()->json($data->toArray());
        });

        $this->postJson('/injectable', ['name' => 'Alice', 'email' => 'alice@example.com'])
            ->assertOk()
            ->assertJson(['name' => 'Bound', 'email' => 'bound@example.com']);
    }

    /**
     * The two guards below are unreachable through a real HTTP request in
     * this test suite: testbench's booted app always has 'request' bound
     * (even with no request simulated), and every fixture routed above is
     * already concrete. Invoking the registered closure directly is the
     * precise way to exercise them.
     */
    public function test_injection_hook_skips_abstract_data_classes(): void
    {
        $callback = $this->beforeResolvingCallback();

        $callback(PaymentMethodData::class, [], $this->app);

        $this->assertFalse($this->app->bound(PaymentMethodData::class));
    }

    public function test_injection_hook_skips_when_no_request_is_bound_anywhere(): void
    {
        $callback = $this->beforeResolvingCallback();
        $bareContainer = new Container;
        // A minimal 'config' binding — everything else about this container
        // is deliberately empty, in particular no 'request' binding at all.
        $bareContainer->instance('config', new ConfigRepository([
            'simple-data-objects' => ['inject_from_request' => true],
        ]));

        $callback(InjectableUserData::class, [], $bareContainer);

        $this->assertFalse($bareContainer->bound(InjectableUserData::class));
    }

    private function beforeResolvingCallback(): \Closure
    {
        $property = new ReflectionProperty(Container::class, 'beforeResolvingCallbacks');
        $property->setAccessible(true);

        return $property->getValue($this->app)[BaseData::class][0];
    }
}
