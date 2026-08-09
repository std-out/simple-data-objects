<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface ProvidesJsonSchema
{
    public function jsonSchema(): array;
}
