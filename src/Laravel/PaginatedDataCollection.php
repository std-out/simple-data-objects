<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

/** @template T of BaseData */
final class PaginatedDataCollection implements JsonSerializable, Responsable
{
    private function __construct(
        /** @var TypedDataCollection<T> */
        public readonly TypedDataCollection $data,
        private readonly LengthAwarePaginator $paginator,
    ) {}

    /**
     * @param  class-string<T>  $dataClass
     * @return self<T>
     */
    public static function of(string $dataClass, LengthAwarePaginator $paginator): self
    {
        return new self(TypedDataCollection::of($dataClass, $paginator->items()), $paginator);
    }

    /** @return array{data: list<array<string, mixed>>, meta: array<string, mixed>, links: array<string, mixed>} */
    public function toArray(): array
    {
        $items = [];

        foreach ($this->data as $item) {
            $items[] = $item->toArray();
        }

        return [
            'data' => $items,
            'meta' => $this->meta(),
            'links' => $this->links(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toResponse($request, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($this->toArray(), $status, $headers);
    }

    /** @return array<string, mixed> */
    private function meta(): array
    {
        return [
            'current_page' => $this->paginator->currentPage(),
            'last_page' => $this->paginator->lastPage(),
            'per_page' => $this->paginator->perPage(),
            'total' => $this->paginator->total(),
            'from' => $this->paginator->firstItem(),
            'to' => $this->paginator->lastItem(),
        ];
    }

    /** @return array<string, mixed> */
    private function links(): array
    {
        return [
            'first' => $this->paginator->url(1),
            'last' => $this->paginator->url($this->paginator->lastPage()),
            'prev' => $this->paginator->previousPageUrl(),
            'next' => $this->paginator->nextPageUrl(),
        ];
    }
}
