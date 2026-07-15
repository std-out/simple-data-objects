<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\LowercaseValuePipe;
use StdOut\SimpleDataObjects\Pipes\TrimValuePipe;
use StdOut\SimpleDataObjects\Pipes\UppercaseValuePipe;

class SignupData extends BaseData
{
    public function __construct(
        #[Pipe(TrimValuePipe::class, LowercaseValuePipe::class)]
        public readonly string $email,

        #[Pipe(UppercaseValuePipe::class)]
        public readonly string $countryCode,

        #[Pipe(LowercaseValuePipe::class)]
        public readonly ?int $lowercasedAge = null,

        #[Pipe(UppercaseValuePipe::class)]
        public readonly ?int $uppercasedAge = null,
    ) {}
}
