<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface ProvidesJsonSchema
{
    /** @return array<string, mixed> */
    public function jsonSchema(): array;
}
