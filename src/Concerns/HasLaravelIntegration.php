<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HasLaravelIntegration
{
    abstract public static function from(mixed $data): static;

    abstract public function toArray(): array;

    public static function fromRequest(Request $request): static
    {
        $data = method_exists($request, 'validated') ? $request->validated() : $request->all();

        return static::fromValidated($data);
    }

    public static function fromModel(Model $model): static
    {
        return static::from($model->toArray());
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse($this->toArray());
    }
}
