<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

enum PaymentType: string
{
    case Card = 'card';
    case Bank = 'bank';
}
