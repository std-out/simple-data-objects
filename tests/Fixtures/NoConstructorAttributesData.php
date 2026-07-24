<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\TrimCast;

class NoConstructorAttributesData extends BaseData
{
    #[Cast(new TrimCast(TrimCast::LOWERCASE))]
    public string $name;

    #[MapPropertyName('user_id')]
    public int $userId;

    #[Hidden]
    public string $password;

    #[IgnoreIfNull]
    public ?string $note = null;

    public Status $status;
}
