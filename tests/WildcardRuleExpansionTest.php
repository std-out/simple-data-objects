<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\DistinctCollectionData;
use StdOut\SimpleDataObjects\Tests\Fixtures\InferredCollectionData;

class WildcardRuleExpansionTest extends TestCase
{
    /** @return iterable<string, array{class-string<BaseData>, array<string, mixed>}> */
    public static function inputs(): iterable
    {
        $item = static fn (mixed $name = 'a', mixed $price = 1.5): array => ['name' => $name, 'price' => $price];

        yield 'valid' => [InferredCollectionData::class, ['name' => 'x', 'items' => [$item(), $item('b')]]];
        yield 'missing and invalid item fields' => [InferredCollectionData::class, ['name' => 'x', 'items' => [['price' => 1], $item(5, 'cheap'), $item()]]];
        yield 'empty list' => [InferredCollectionData::class, ['name' => 'x', 'items' => []]];
        yield 'missing list' => [InferredCollectionData::class, ['name' => 'x']];
        yield 'string keys' => [InferredCollectionData::class, ['name' => 'x', 'items' => ['first' => ['price' => 1], 'second' => $item()]]];
        yield 'non-array item' => [InferredCollectionData::class, ['name' => 'x', 'items' => ['oops', ['price' => 1]]]];
        yield 'list is a string' => [InferredCollectionData::class, ['name' => 'x', 'items' => 'oops']];
        yield 'distinct duplicates' => [DistinctCollectionData::class, ['items' => [['sku' => 'a'], ['sku' => 'a'], ['sku' => 'b']]]];
    }

    /**
     * @param  class-string<BaseData>  $class
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('inputs')]
    public function test_errors_match_laravel_wildcard_validation(string $class, array $input): void
    {
        $factory = new Factory(new Translator(new ArrayLoader, 'en'));
        $expected = $factory->make($input, MetadataRegistry::get($class)->validationRules)->errors()->toArray();

        [$data, $rules] = MetadataRegistry::get($class)->validationInput($input);

        $this->assertSame($expected, $factory->make($data, $rules)->errors()->toArray());
    }

    public function test_index_independent_rules_are_expanded_per_item(): void
    {
        [, $rules] = MetadataRegistry::get(InferredCollectionData::class)->validationInput(['name' => 'x', 'items' => [[], []]]);

        $this->assertSame(['name', 'items', 'items.0.name', 'items.1.name', 'items.0.price', 'items.1.price'], array_keys($rules));
    }

    public function test_distinct_and_non_array_items_keep_the_wildcard(): void
    {
        [, $distinct] = MetadataRegistry::get(DistinctCollectionData::class)->validationInput(['items' => [['sku' => 'a']]]);
        [, $mixed] = MetadataRegistry::get(InferredCollectionData::class)->validationInput(['name' => 'x', 'items' => ['oops']]);

        $this->assertArrayHasKey('items.*.sku', $distinct);
        $this->assertArrayHasKey('items.*.name', $mixed);
    }
}
