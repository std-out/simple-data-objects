<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Tests\Fixtures\AliasedValidatedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\BoundedAmountData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CustomValidationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\UnevaluableRuleData;
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

    public function test_set_validator_factory_is_used(): void
    {
        $factory = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));
        BaseData::setValidatorFactory($factory);

        $user = ValidatedUserData::fromValidated(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame('Alice', $user->name);
    }

    public function test_validator_factory_resolved_from_container(): void
    {
        $factory = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));

        $container = new Container;
        $container->instance('validator', $factory);
        Container::setInstance($container);

        $ref = new \ReflectionProperty(BaseData::class, 'validatorFactory');
        $ref->setValue(null, null);

        try {
            $user = ValidatedUserData::fromValidated(['name' => 'Alice', 'email' => 'alice@example.com']);
            $this->assertSame('Alice', $user->name);
        } finally {
            Container::setInstance(null);
            $ref->setValue(null, null);
        }
    }

    public function test_validate_skips_validation_when_class_has_no_rules(): void
    {
        // UserData has no #[Rules] attributes, so validationRules === []
        // This covers BaseData::validate() early return at line 100
        UserData::validate(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertTrue(true);
    }

    public function test_input_alias_is_validated_against_the_field_rules(): void
    {
        $this->expectException(ValidationException::class);

        AliasedValidatedData::fromValidated(['e_mail' => 'not-an-email']);
    }

    public function test_output_name_alias_is_validated_against_the_field_rules(): void
    {
        $this->expectException(ValidationException::class);

        AliasedValidatedData::fromValidated(['email' => 'a@example.com', 'card_number' => '12<script>']);
    }

    public function test_required_field_supplied_through_an_alias_passes(): void
    {
        $data = AliasedValidatedData::fromValidated(['mail' => 'a@example.com', 'card_number' => '4111111111111111']);

        $this->assertSame('a@example.com', $data->email);
        $this->assertSame('4111111111111111', $data->cardNumber);
    }

    public function test_primary_name_wins_over_an_alias_like_hydration_does(): void
    {
        $data = AliasedValidatedData::fromValidated(['email' => 'a@example.com', 'e_mail' => 'not-an-email']);

        $this->assertSame('a@example.com', $data->email);
    }

    public function test_validate_checks_alias_values(): void
    {
        $this->expectException(ValidationException::class);

        AliasedValidatedData::validate(['e_mail' => 'not-an-email']);
    }

    public function test_from_validated_result_reports_alias_violations_under_the_primary_name(): void
    {
        $result = AliasedValidatedData::fromValidatedResult(['e_mail' => 'not-an-email']);

        $this->assertFalse($result->ok());
        $this->assertArrayHasKey('email', $result->errors());
    }

    /** @return iterable<string, array{0: mixed}> */
    public static function unevaluableAmounts(): iterable
    {
        yield 'JSON 1e400' => [json_decode('{"amount":1e400}', true)['amount']];
        yield 'INF' => [INF];
        yield 'NAN' => [NAN];
    }

    #[DataProvider('unevaluableAmounts')]
    public function test_from_validated_throws_only_validation_exception_for_non_finite_numbers(mixed $amount): void
    {
        $this->expectException(ValidationException::class);

        BoundedAmountData::fromValidated(['amount' => $amount]);
    }

    #[DataProvider('unevaluableAmounts')]
    public function test_validate_throws_only_validation_exception_for_non_finite_numbers(mixed $amount): void
    {
        $this->expectException(ValidationException::class);

        BoundedAmountData::validate(['amount' => $amount]);
    }

    /** @return iterable<string, array{0: string}> */
    public static function validatingMethods(): iterable
    {
        yield 'fromValidated' => ['fromValidated'];
        yield 'validate' => ['validate'];
    }

    #[DataProvider('validatingMethods')]
    public function test_a_rule_that_cannot_evaluate_the_input_is_reported_as_a_validation_error(string $method): void
    {
        $factory = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));
        $factory->extend('unevaluable', static fn (): bool => throw new \RuntimeException('rule failed'));
        BaseData::setValidatorFactory($factory);

        try {
            UnevaluableRuleData::$method(['name' => 'Alice']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['$input' => ['Input could not be validated.']], $e->errors());
        }
    }

    public function test_validation_exception_thrown_by_a_rule_is_passed_through(): void
    {
        $factory = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));
        $thrown = new ValidationException($factory->make([], []));
        $factory->extend('unevaluable', static fn (): bool => throw $thrown);
        BaseData::setValidatorFactory($factory);

        try {
            UnevaluableRuleData::validate(['name' => 'Alice']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame($thrown, $e);
        }
    }

    public function test_validator_exception_class_is_honoured(): void
    {
        $factory = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));
        $factory->resolver(static function (...$args): Validator {
            $validator = new Validator(...$args);
            $validator->setException(CustomValidationException::class);

            return $validator;
        });
        BaseData::setValidatorFactory($factory);

        $this->expectException(CustomValidationException::class);

        BoundedAmountData::fromValidated(['amount' => 101]);
    }
}
