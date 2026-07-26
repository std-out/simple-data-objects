<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class CastableCardPaymentData extends CastablePaymentData
{
    public function __construct(
        public readonly string $last4,
        public readonly string $type = 'card',
    ) {}
}
