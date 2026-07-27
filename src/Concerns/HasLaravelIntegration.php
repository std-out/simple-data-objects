<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->toArray());
    }
}
