<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ValidatedUserData;

class ValidationTest extends TestCase
{
    public function test_valid_data_passes(): void
    {
        $user = ValidatedUserData::fromValidated([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame('Alice', $user->name);
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(ValidationException::class);

        ValidatedUserData::fromValidated([
            'name' => 'Alice',
            'email' => 'not-an-email',
        ]);
    }

    public function test_missing_required_field_throws(): void
    {
        $this->expectException(ValidationException::class);

        ValidatedUserData::fromValidated(['name' => 'Alice']);
    }

    public function test_validation_errors_contain_field_names(): void
    {
        try {
            ValidatedUserData::fromValidated([
                'name' => 'Alice',
                'email' => 'bad',
            ]);

            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_multiple_errors_reported_at_once(): void
    {
        try {
            ValidatedUserData::fromValidated([]);

            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_nullable_field_passes_when_absent(): void
    {
        $user = ValidatedUserData::fromValidated([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertNull($user->username);
    }

    public function test_nullable_field_validates_when_present(): void
    {
        $this->expectException(ValidationException::class);

        ValidatedUserData::fromValidated([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'username' => 'ab',
        ]);
    }

    public function test_validate_method_alone_throws_on_invalid(): void
    {
        $this->expectException(ValidationException::class);

        ValidatedUserData::validate(['name' => 'Alice', 'email' => 'bad']);
    }

    public function test_validate_method_passes_on_valid(): void
    {
        ValidatedUserData::validate([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertTrue(true);
    }

    public function test_from_skips_validation(): void
    {
        $user = ValidatedUserData::from([
            'name' => 'Alice',
            'email' => 'not-an-email',
        ]);

        $this->assertSame('not-an-email', $user->email);
    }

    public function test_class_without_rules_skips_validation(): void
    {
        $user = UserData::fromValidated([
            'name' => 'Alice',
            'email' => 'not-an-email',
        ]);

        $this->assertSame('not-an-email', $user->email);
    }
}
