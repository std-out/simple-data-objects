<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Laravel\PaginatedDataCollection;
use StdOut\SimpleDataObjects\Tests\Fixtures\LaravelUserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class PaginatedDataCollectionTest extends TestCase
{
    private function paginator(int $currentPage = 1): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: new Collection([
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com'],
            ]),
            total: 5,
            perPage: 2,
            currentPage: $currentPage,
            options: ['path' => 'https://example.test/users'],
        );
    }

    public function test_of_hydrates_items_into_typed_data_collection(): void
    {
        $page = PaginatedDataCollection::of(UserData::class, $this->paginator());

        $this->assertInstanceOf(TypedDataCollection::class, $page->data);
        $this->assertCount(2, $page->data);
        $this->assertInstanceOf(UserData::class, $page->data->first());
        $this->assertSame('Alice', $page->data->first()->name);
    }

    public function test_has_laravel_integration_paginated_collection_delegates_to_of(): void
    {
        $page = LaravelUserData::paginatedCollection($this->paginator());

        $this->assertInstanceOf(PaginatedDataCollection::class, $page);
        $this->assertSame('Alice', $page->data->first()->name);
    }

    public function test_to_array_shape(): void
    {
        $page = PaginatedDataCollection::of(UserData::class, $this->paginator());
        $array = $page->toArray();

        $this->assertSame(
            [
                ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null],
                ['name' => 'Bob', 'email' => 'bob@example.com', 'phone' => null],
            ],
            $array['data'],
        );
        $this->assertSame(1, $array['meta']['current_page']);
        $this->assertSame(3, $array['meta']['last_page']);
        $this->assertSame(2, $array['meta']['per_page']);
        $this->assertSame(5, $array['meta']['total']);
    }

    public function test_links_reflect_current_page(): void
    {
        $page = PaginatedDataCollection::of(UserData::class, $this->paginator(currentPage: 2));
        $links = $page->toArray()['links'];

        $this->assertSame('https://example.test/users?page=1', $links['first']);
        $this->assertSame('https://example.test/users?page=3', $links['last']);
        $this->assertSame('https://example.test/users?page=1', $links['prev']);
        $this->assertSame('https://example.test/users?page=3', $links['next']);
    }

    public function test_json_serialize_matches_to_array(): void
    {
        $page = PaginatedDataCollection::of(UserData::class, $this->paginator());

        $this->assertSame($page->toArray(), $page->jsonSerialize());
    }

    public function test_to_response_returns_json_response(): void
    {
        $page = PaginatedDataCollection::of(UserData::class, $this->paginator());
        $response = $page->toResponse(new Request, 206, ['X-Test' => 'yes']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('yes', $response->headers->get('X-Test'));
        $this->assertSame($page->toArray(), $response->getData(true));
    }
}
