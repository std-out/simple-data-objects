<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface BindsToField
{
    /** @param class-string $class */
    public function forField(string $class, string $field): static;
}
