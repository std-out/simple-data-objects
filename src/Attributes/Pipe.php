<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
final class Pipe
{
    /** @var list<class-string> */
    public readonly array $pipes;

    /**
     * On class: expects class-string<DataPipe>[] — transforms the full input array.
     * On parameter: expects class-string<ValuePipe>[] — transforms the individual value.
     */
    public function __construct(string ...$pipes)
    {
        $this->pipes = array_values($pipes);
    }
}
