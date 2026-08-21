<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Laravel\PaginatedDataCollection;
use StdOut\SimpleDataObjects\Tests\Fixtures\AuthData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextFlattenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextHiddenData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextNestedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ContextResponseData;

class SerializationContextTest extends TestCase
{
    private function data(): ContextHiddenData
    {
        return ContextHiddenData::from(['name' => 'Alice', 'internalNote' => 'flagged', 'secret' => 's3cr3t']);
    }

    public function test_default_context_hides_the_except_field(): void
    {
        $array = $this->data()->toArray();

        $this->assertSame(['name' => 'Alice'], $array);
    }

    public function test_matching_context_includes_the_except_field(): void
    {
        $array = $this->data()->toArray('admin');

        $this->assertSame(['name' => 'Alice', 'internalNote' => 'flagged'], $array);
    }

    public function test_non_matching_context_still_hides_the_field(): void
    {
        $array = $this->data()->toArray('support');

        $this->assertSame(['name' => 'Alice'], $array);
    }

    public function test_bare_hidden_field_is_never_shown_in_any_context(): void
    {
        $array = $this->data()->toArray('admin');

        $this->assertArrayNotHasKey('secret', $array);
    }

    public function test_bare_hidden_regression_unaffected_by_the_except_feature(): void
    {
        $auth = AuthData::from(['username' => 'alice', 'password' => 's3cr3t']);

        $this->assertArrayNotHasKey('password', $auth->toArray());
        $this->assertArrayNotHasKey('password', $auth->toArray('admin'));
    }

    public function test_nested_data_object_propagates_context(): void
    {
        $data = ContextNestedData::from([
            'label' => 'Ticket',
            'detail' => ['name' => 'Alice', 'internalNote' => 'flagged', 'secret' => 's3cr3t'],
        ]);

        $this->assertSame(['label' => 'Ticket', 'detail' => ['name' => 'Alice']], $data->toArray());
        $this->assertSame(
            ['label' => 'Ticket', 'detail' => ['name' => 'Alice', 'internalNote' => 'flagged']],
            $data->toArray('admin'),
        );
    }

    public function test_data_collection_items_propagate_context(): void
    {
        $data = ContextCollectionData::from([
            'label' => 'Team',
            'items' => [
                ['name' => 'Alice', 'internalNote' => 'a', 'secret' => 'x'],
                ['name' => 'Bob', 'internalNote' => 'b', 'secret' => 'y'],
            ],
        ]);

        $this->assertSame(
            [['name' => 'Alice'], ['name' => 'Bob']],
            $data->toArray()['items'],
        );
        $this->assertSame(
            [['name' => 'Alice', 'internalNote' => 'a'], ['name' => 'Bob', 'internalNote' => 'b']],
            $data->toArray('admin')['items'],
        );
    }

    public function test_flatten_propagates_context(): void
    {
        $data = ContextFlattenData::from([
            'label' => 'Ticket',
            'name' => 'Alice',
            'internalNote' => 'flagged',
            'secret' => 's3cr3t',
        ]);

        $this->assertSame(['label' => 'Ticket', 'name' => 'Alice'], $data->toArray());
        $this->assertSame(
            ['label' => 'Ticket', 'name' => 'Alice', 'internalNote' => 'flagged'],
            $data->toArray('admin'),
        );
    }

    public function test_to_json_accepts_a_context(): void
    {
        $json = $this->data()->toJson(context: 'admin');

        $this->assertSame(['name' => 'Alice', 'internalNote' => 'flagged'], json_decode($json, true));
    }

    public function test_defined_only_accepts_a_context(): void
    {
        $this->assertSame($this->data()->toArray('admin'), $this->data()->definedOnly('admin'));
    }

    public function test_only_and_except_stay_context_free(): void
    {
        $data = $this->data();

        $this->assertSame(['name' => 'Alice'], $data->only('name', 'internalNote'));
        $this->assertSame(['name' => 'Alice'], $data->except('secret'));
    }

    public function test_json_serialize_stays_context_free(): void
    {
        $this->assertSame($this->data()->toArray(), $this->data()->jsonSerialize());
    }

    public function test_to_response_accepts_a_context(): void
    {
        $data = ContextResponseData::from(['title' => 'T', 'internalNote' => 'flagged']);
        $request = new Request;

        $default = $data->toResponse($request);
        $admin = $data->toResponse($request, context: 'admin');

        $this->assertSame(['title' => 'T'], json_decode($default->getContent(), true));
        $this->assertSame(['title' => 'T', 'internalNote' => 'flagged'], json_decode($admin->getContent(), true));
    }

    public function test_paginated_data_collection_propagates_context(): void
    {
        $paginator = new LengthAwarePaginator(
            items: new Collection([
                ['name' => 'Alice', 'internalNote' => 'a', 'secret' => 'x'],
            ]),
            total: 1,
            perPage: 10,
            currentPage: 1,
            options: ['path' => 'https://example.test/items'],
        );

        $page = PaginatedDataCollection::of(ContextHiddenData::class, $paginator);

        $this->assertSame([['name' => 'Alice']], $page->toArray()['data']);
        $this->assertSame([['name' => 'Alice', 'internalNote' => 'a']], $page->toArray('admin')['data']);
    }
}
