<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\PipelineRunner;
use StdOut\SimpleDataObjects\Tests\Fixtures\AppendSuffixPipe;
use StdOut\SimpleDataObjects\Tests\Fixtures\NullifiedFormData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PartiallyPipedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SignupData;
use StdOut\SimpleDataObjects\Tests\Fixtures\TrimmedContactData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UpperCasePipe;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class PipeTest extends TestCase
{
    protected function tearDown(): void
    {
        MetadataRegistry::flush();
    }

    public function test_trim_strings_pipe_trims_all_string_values(): void
    {
        $result = TrimmedContactData::from(['name' => '  Alice  ', 'email' => ' alice@example.com ']);

        $this->assertSame('Alice', $result->name);
        $this->assertSame('alice@example.com', $result->email);
    }

    public function test_nullify_empty_strings_pipe_converts_empty_to_null(): void
    {
        $result = NullifiedFormData::from(['name' => 'Alice', 'bio' => '']);

        $this->assertNull($result->bio);
    }

    public function test_trim_then_nullify_whitespace_only_string(): void
    {
        $result = NullifiedFormData::from(['name' => 'Alice', 'bio' => '   ']);

        $this->assertNull($result->bio);
    }

    public function test_class_without_pipe_is_unaffected(): void
    {
        $result = UserData::from(['name' => '  Alice  ', 'email' => ' a@b.com ']);

        $this->assertSame('  Alice  ', $result->name);
    }

    // --- Parameter-level #[Pipe] (ValuePipe) ---

    public function test_parameter_pipe_trims_only_piped_property(): void
    {
        $result = PartiallyPipedData::from([
            'name' => '  Alice  ',
            'email' => '  alice@example.com  ',
            'bio' => '  dev  ',
        ]);

        $this->assertSame('Alice', $result->name);           // trimmed
        $this->assertSame('  alice@example.com  ', $result->email); // NOT trimmed
        $this->assertSame('dev', $result->bio);              // trimmed (first pipe)
    }

    public function test_parameter_pipe_chain_trim_then_nullify(): void
    {
        $result = PartiallyPipedData::from([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'bio' => '   ',
        ]);

        $this->assertNull($result->bio); // trimmed to '' then nullified
    }

    public function test_parameter_pipe_nullify_passes_null_through(): void
    {
        $result = PartiallyPipedData::from([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        $this->assertNull($result->bio); // default null, pipe passes it through
    }

    public function test_pipeline_runner_run_on_value_empty_pipes_noop(): void
    {
        $this->assertSame('  hello  ', PipelineRunner::runOnValue('  hello  ', 'field', []));
    }

    public function test_pipeline_runner_executes_pipes_in_declaration_order(): void
    {
        // UpperCasePipe runs first: 'alice' → 'ALICE'
        // AppendSuffixPipe runs second: 'ALICE' → 'ALICE_ok'
        $result = PipelineRunner::run(
            ['name' => 'alice'],
            'AnyClass',
            [UpperCasePipe::class, AppendSuffixPipe::class],
        );

        $this->assertSame('ALICE_ok', $result['name']);
    }

    public function test_pipeline_runner_empty_pipes_returns_data_unchanged(): void
    {
        $data = ['name' => 'Alice'];

        $this->assertSame($data, PipelineRunner::run($data, 'AnyClass', []));
    }

    public function test_pipe_transforms_data_before_hydration(): void
    {
        // UpperCasePipe uppercases values before hydration
        $result = PipelineRunner::run(
            ['name' => 'alice', 'email' => 'alice@example.com'],
            TrimmedContactData::class,
            [UpperCasePipe::class],
        );

        $this->assertSame('ALICE', $result['name']);
        $this->assertSame('ALICE@EXAMPLE.COM', $result['email']);
    }

    // --- LowercaseValuePipe / UppercaseValuePipe ---

    public function test_lowercase_value_pipe_lowercases_string(): void
    {
        $result = SignupData::from([
            'email' => '  Alice@Example.COM',
            'countryCode' => 'us',
        ]);

        $this->assertSame('alice@example.com', $result->email);
    }

    public function test_uppercase_value_pipe_uppercases_string(): void
    {
        $result = SignupData::from([
            'email' => 'alice@example.com',
            'countryCode' => 'us',
        ]);

        $this->assertSame('US', $result->countryCode);
    }

    public function test_lowercase_value_pipe_passes_non_string_through_untouched(): void
    {
        $result = SignupData::from([
            'email' => 'alice@example.com',
            'countryCode' => 'US',
            'lowercasedAge' => 30,
        ]);

        $this->assertSame(30, $result->lowercasedAge);
    }

    public function test_uppercase_value_pipe_passes_non_string_through_untouched(): void
    {
        $result = SignupData::from([
            'email' => 'alice@example.com',
            'countryCode' => 'US',
            'uppercasedAge' => 30,
        ]);

        $this->assertSame(30, $result->uppercasedAge);
    }

    public function test_lowercase_value_pipe_passes_null_through(): void
    {
        $result = SignupData::from([
            'email' => 'alice@example.com',
            'countryCode' => 'US',
        ]);

        $this->assertNull($result->lowercasedAge);
    }
}
