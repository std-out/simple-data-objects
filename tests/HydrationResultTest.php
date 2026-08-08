<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\HydrationResult;

class HydrationResultTest extends TestCase
{
    public function test_success_is_ok_and_exposes_the_value(): void
    {
        $result = HydrationResult::success('a value');

        $this->assertTrue($result->ok());
        $this->assertSame('a value', $result->value());
        $this->assertSame('a value', $result->valueOrNull());
        $this->assertSame([], $result->errors());
    }

    public function test_failure_is_not_ok_and_exposes_errors(): void
    {
        $result = HydrationResult::failure(['name' => 'Missing required field.']);

        $this->assertFalse($result->ok());
        $this->assertSame(['name' => 'Missing required field.'], $result->errors());
        $this->assertNull($result->valueOrNull());
    }

    public function test_value_throws_on_a_failed_result(): void
    {
        $result = HydrationResult::failure(['name' => 'Missing required field.']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Cannot read value\(\) of a failed HydrationResult/');

        $result->value();
    }
}
