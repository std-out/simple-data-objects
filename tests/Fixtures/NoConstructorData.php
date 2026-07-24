<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class NoConstructorData extends BaseData
{
    public static string $ignoredStatic = 'static-value';

    public ?string $optional = null;

    public string $required;

    public readonly string $id;

    public int $priority = 1;

    public $untyped;

    private string $secret = 'hidden';

    public function secret(): string
    {
        return $this->secret;
    }
}
