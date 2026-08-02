<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\LaravelUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\WrappedOrderData;

class HasLaravelIntegrationTest extends TestCase
{
    public function test_from_request_uses_all_for_plain_request(): void
    {
        $request = Request::create('/', 'POST', ['name' => 'Alice', 'email' => 'alice@example.com']);
        $data = LaravelUserData::fromRequest($request);

        $this->assertSame('Alice', $data->name);
        $this->assertSame('alice@example.com', $data->email);
    }

    public function test_from_request_uses_validated_when_method_exists(): void
    {
        $request = new class extends Request
        {
            public function __construct()
            {
                parent::__construct();
            }

            public function validated(string|array|null $key = null, mixed $default = null): mixed
            {
                return ['name' => 'Bob', 'email' => 'bob@example.com'];
            }
        };

        $data = LaravelUserData::fromRequest($request);

        $this->assertSame('Bob', $data->name);
        $this->assertSame('bob@example.com', $data->email);
    }

    public function test_from_model_hydrates_from_model_attributes(): void
    {
        $model = $this->createMock(Model::class);
        $model->method('attributesToArray')->willReturn(['name' => 'Carol', 'email' => 'carol@example.com']);

        $data = LaravelUserData::fromModel($model);

        $this->assertSame('Carol', $data->name);
        $this->assertSame('carol@example.com', $data->email);
    }

    public function test_to_response_returns_json_response(): void
    {
        $data = LaravelUserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $response = $data->toResponse(null);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            $response->getData(true),
        );
    }

    public function test_to_response_accepts_status_and_headers(): void
    {
        $data = LaravelUserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $response = $data->toResponse(null, 201, ['X-Test' => 'yes']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('yes', $response->headers->get('X-Test'));
    }

    public function test_to_response_wraps_with_wrap_in(): void
    {
        $data = WrappedOrderData::from(['title' => 'Widget']);
        $response = $data->toResponse(null);

        $this->assertSame(['data' => ['title' => 'Widget']], $response->getData(true));
    }

    public function test_to_array_is_not_wrapped_by_wrap_in(): void
    {
        $data = WrappedOrderData::from(['title' => 'Widget']);

        $this->assertSame(['title' => 'Widget'], $data->toArray());
    }
}
