<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\ClassAndFlattenPipedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CommaSplitValuePipe;
use StdOut\SimpleDataObjects\Tests\Fixtures\CountingArrayValuePipe;
use StdOut\SimpleDataObjects\Tests\Fixtures\FlattenPipedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridPipedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorPipedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowingPipeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThrowingValuePipeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ValidatedPipedData;

class PipesBeforeValidationTest extends TestCase
{
    protected function setUp(): void
    {
        MetadataRegistry::flush();
        CommaSplitValuePipe::$calls = 0;
        CountingArrayValuePipe::$calls = 0;
    }

    public function test_rules_check_the_piped_value(): void
    {
        try {
            ValidatedPipedData::fromValidated(['name' => '  ab  ']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['name'], array_keys($e->errors()));
        }

        $this->assertSame(['name'], array_keys(ValidatedPipedData::fromValidatedResult(['name' => '  ab  '])->errors()));
        $this->assertSame(['name', 'tags'], array_keys(ValidatedPipedData::fromValidatedResult(['name' => 'ab', 'tags' => 'a'])->errors()));
    }

    public function test_piped_values_are_validated_and_hydrated_with_each_pipe_run_once(): void
    {
        $data = ValidatedPipedData::fromValidated(['name' => '  abc ', 'tags' => 'a,b']);

        $this->assertSame('abc', $data->name);
        $this->assertSame(['a', 'b'], $data->tags);
        $this->assertSame(1, CommaSplitValuePipe::$calls);

        $result = ValidatedPipedData::fromValidatedResult(['name' => '  abc ', 'labels' => 'a,b']);

        $this->assertSame(['a', 'b'], $result->value()->tags);
        $this->assertSame(2, CommaSplitValuePipe::$calls);

        $this->assertSame(['a', 'b'], ValidatedPipedData::fromValidated(['name' => 'abc', 'tags' => 'a,b', 'labels' => 'c,d'])->tags);
        $this->assertSame(3, CommaSplitValuePipe::$calls);
    }

    public function test_validate_runs_the_pipes_first(): void
    {
        ValidatedPipedData::validate(['name' => 'abc', 'labels' => 'a,b']);

        $this->expectException(ValidationException::class);

        ValidatedPipedData::validate(['name' => 'abc', 'labels' => 'a']);
    }

    public function test_unknown_keys_are_checked_against_the_raw_input(): void
    {
        try {
            ValidatedPipedData::fromValidated(['name' => 'abc', 'extra' => 1]);
            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertSame(['extra'], $e->unknownKeys);
        }

        $this->assertSame(['$unknown'], array_keys(ValidatedPipedData::fromValidatedResult(['name' => 'abc', 'extra' => 1])->errors()));

        try {
            ValidatedPipedData::fromValidated(['name' => 'abc', 7 => 'x']);
            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertSame(['7'], $e->unknownKeys);
        }
    }

    public function test_a_failing_class_pipe_is_a_pipeline_error(): void
    {
        try {
            ThrowingPipeData::fromValidated(['name' => 'Alice']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['$pipeline' => ['Input could not be processed.']], $e->errors());
        }

        $this->assertSame(['$pipeline' => 'pipe exploded'], ThrowingPipeData::fromValidatedResult(['name' => 'Alice'])->errors());
        $this->assertSame(['$pipeline' => 'pipe exploded'], ThrowingPipeData::fromValidatedResult([])->errors());
    }

    public function test_a_failing_property_pipe_is_reported_under_the_field(): void
    {
        try {
            ThrowingValuePipeData::validate(['name' => 'Alice', 'nickname' => 'Al']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['nickname' => ['Input could not be processed.']], $e->errors());
        }

        $this->assertSame(['nickname' => 'value pipe exploded'], ThrowingValuePipeData::fromValidatedResult(['name' => 'Alice', 'nickname' => 'Al'])->errors());
        $this->assertSame(['name' => 'validation.required', 'nickname' => 'value pipe exploded'], ThrowingValuePipeData::fromValidatedResult(['nickname' => 'Al'])->errors());
        $this->assertNull(ThrowingValuePipeData::fromValidated(['name' => 'Alice'])->nickname);
    }

    public function test_flattened_property_pipes_run_once_at_hydration(): void
    {
        $this->assertFalse(MetadataRegistry::get(FlattenPipedData::class)->pipesBeforeValidation);
        $this->assertTrue(MetadataRegistry::get(ClassAndFlattenPipedData::class)->pipesBeforeValidation);

        $data = ClassAndFlattenPipedData::fromValidated(['code' => ' abc ', 'user' => 'ignored', 'name' => 'Alice', 'email' => 'a@example.com']);

        $this->assertSame('abc', $data->code);
        $this->assertSame('Alice', $data->user->name);
        $this->assertSame(1, CountingArrayValuePipe::$calls);
    }

    public function test_classes_with_assigned_properties_hydrate_the_piped_input(): void
    {
        $hybrid = HybridPipedData::fromValidated(['name' => ' Alice ', 'bio' => '']);

        $this->assertSame('Alice', $hybrid->name);
        $this->assertNull($hybrid->bio);
        $this->assertNull(HybridPipedData::fromValidatedResult(['name' => 'Alice', 'bio' => ' '])->value()->bio);

        $this->assertSame('abc', NoConstructorPipedData::fromValidated(['name' => ' abc '])->name);
        $this->assertSame(['name'], array_keys(NoConstructorPipedData::fromValidatedResult(['name' => ' ab '])->errors()));
    }

    public function test_classes_without_pipes_or_rules_skip_validation(): void
    {
        $this->assertFalse(MetadataRegistry::get(UserData::class)->pipesBeforeValidation);

        UserData::validate(42);

        $this->addToAssertionCount(1);
    }
}
