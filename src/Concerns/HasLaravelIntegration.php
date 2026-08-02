<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StdOut\SimpleDataObjects\Laravel\PaginatedDataCollection;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

trait HasLaravelIntegration
{
    abstract public static function from(mixed $data): static;

    abstract public function toArray(): array;

    public static function fromRequest(Request $request): static
    {
        $data = method_exists($request, 'validated') ? $request->validated() : $request->all();

        return static::fromValidated($data);
    }

    /**
     * Hydrates from the model's own attributes; relations are included only
     * for parameters marked #[WhenLoaded], and only when actually loaded.
     */
    public static function fromModel(Model $model): static
    {
        $data = $model->attributesToArray();

        foreach (MetadataRegistry::get(static::class)->parameters as $param) {
            if ($param->whenLoadedRelation !== null && $model->relationLoaded($param->whenLoadedRelation)) {
                $data[$param->inputName] = $model->getRelation($param->whenLoadedRelation);
            }
        }

        return static::from($data);
    }

    public function toResponse($request, int $status = 200, array $headers = []): JsonResponse
    {
        $wrapIn = MetadataRegistry::get(static::class)->wrapIn;
        $data = $this->toArray();

        return new JsonResponse($wrapIn !== null ? [$wrapIn => $data] : $data, $status, $headers);
    }

    /**
     * @return PaginatedDataCollection<static>
     */
    public static function paginatedCollection(LengthAwarePaginator $paginator): PaginatedDataCollection
    {
        return PaginatedDataCollection::of(static::class, $paginator);
    }
}
