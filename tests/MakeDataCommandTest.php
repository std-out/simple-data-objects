<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use StdOut\SimpleDataObjects\Tests\Fixtures\GeneratorOrderModel;
use StdOut\SimpleDataObjects\Tests\Fixtures\TestProductModel;
use StdOut\SimpleDataObjects\Tests\Laravel\TestCase;

/**
 * Schema::hasTable()/getColumns() are mocked rather than run against a real
 * database — this suite is testing our own column-type-to-PHP-type mapping
 * and stub generation, not Laravel's schema introspection, so a deterministic
 * fixed column shape is more precise than whatever a given DB driver's
 * grammar happens to normalize a migration down to.
 */
class MakeDataCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Data'));
        File::deleteDirectory(app_path('Dto'));

        parent::tearDown();
    }

    private function mockProductColumns(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('test_products')
            ->andReturn(true);

        Schema::shouldReceive('getColumns')
            ->with('test_products')
            ->andReturn([
                ['name' => 'id', 'type_name' => 'integer', 'nullable' => false, 'auto_increment' => true, 'generation' => null],
                ['name' => 'name', 'type_name' => 'varchar', 'nullable' => false, 'auto_increment' => false, 'generation' => null],
                ['name' => 'description', 'type_name' => 'text', 'nullable' => true, 'auto_increment' => false, 'generation' => null],
                ['name' => 'quantity', 'type_name' => 'integer', 'nullable' => false, 'auto_increment' => false, 'generation' => null],
                ['name' => 'active', 'type_name' => 'boolean', 'nullable' => true, 'auto_increment' => false, 'generation' => null],
                ['name' => 'meta', 'type_name' => 'json', 'nullable' => true, 'auto_increment' => false, 'generation' => null],
                ['name' => 'legacy_slug', 'type_name' => 'unknown_type', 'nullable' => false, 'auto_increment' => false, 'generation' => null],
                ['name' => 'full_name', 'type_name' => 'varchar', 'nullable' => false, 'auto_increment' => false, 'generation' => ['type' => 'stored', 'expression' => "first_name || ' ' || last_name"]],
            ]);
    }

    public function test_bare_stub_has_no_properties_or_todo(): void
    {
        $exit = Artisan::call('make:data', ['name' => 'PlainData']);

        $this->assertSame(0, $exit);

        $contents = file_get_contents(app_path('Data/PlainData.php'));
        $this->assertStringContainsString('namespace App\\Data;', $contents);
        $this->assertStringContainsString('class PlainData extends BaseData', $contents);
        $this->assertStringNotContainsString('TODO', $contents);
        $this->assertStringNotContainsString('TransformKeys', $contents);
    }

    public function test_from_model_generates_typed_properties_and_skips_the_primary_key_and_generated_column(): void
    {
        $this->mockProductColumns();

        $exit = Artisan::call('make:data', [
            'name' => 'ProductData',
            '--from-model' => TestProductModel::class,
        ]);

        $this->assertSame(0, $exit);

        $contents = file_get_contents(app_path('Data/ProductData.php'));
        $this->assertStringContainsString('#[TransformKeys(TransformKeys::SNAKE_CASE)]', $contents);
        $this->assertStringContainsString('public readonly string $name,', $contents);
        $this->assertStringContainsString('public readonly ?string $description = null,', $contents);
        $this->assertStringContainsString('public readonly int $quantity,', $contents);
        $this->assertStringContainsString('public readonly ?bool $active = null,', $contents);
        $this->assertStringContainsString('public readonly ?array $meta = null,', $contents);
        $this->assertStringContainsString('public readonly mixed $legacySlug,', $contents);
        $this->assertStringNotContainsString('$id', $contents);
        $this->assertStringNotContainsString('$fullName', $contents);
        $this->assertStringNotContainsString('Rules', $contents);
    }

    public function test_rules_option_adds_rules_attributes_inferred_from_columns(): void
    {
        $this->mockProductColumns();

        $exit = Artisan::call('make:data', [
            'name' => 'ProductRulesData',
            '--from-model' => TestProductModel::class,
            '--rules' => true,
        ]);

        $this->assertSame(0, $exit);

        $contents = file_get_contents(app_path('Data/ProductRulesData.php'));
        $this->assertStringContainsString('use StdOut\\SimpleDataObjects\\Attributes\\Rules;', $contents);
        $this->assertStringContainsString("#[Rules(['required', 'string'])]", $contents);
        $this->assertStringContainsString("#[Rules(['nullable', 'string'])]", $contents);
        $this->assertStringContainsString("#[Rules(['required', 'integer'])]", $contents);
        $this->assertStringContainsString("#[Rules(['nullable', 'boolean'])]", $contents);
        $this->assertStringContainsString("#[Rules(['nullable', 'array'])]", $contents);
        $this->assertStringContainsString("#[Rules(['required'])]", $contents);
    }

    public function test_collection_option_adds_a_doc_comment_pointing_at_static_collection(): void
    {
        $exit = Artisan::call('make:data', ['name' => 'CollectionData', '--collection' => true]);

        $this->assertSame(0, $exit);

        $contents = file_get_contents(app_path('Data/CollectionData.php'));
        $this->assertStringContainsString('@see static::collection()', $contents);
    }

    public function test_rules_without_from_model_warns_and_generates_a_bare_stub(): void
    {
        $exit = Artisan::call('make:data', ['name' => 'NoModelRulesData', '--rules' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('has no effect without --from-model', Artisan::output());

        $contents = file_get_contents(app_path('Data/NoModelRulesData.php'));
        $this->assertStringNotContainsString('#[Rules', $contents);
    }

    public function test_unknown_model_class_fails_without_writing_a_file(): void
    {
        $exit = Artisan::call('make:data', [
            'name' => 'MissingModelData',
            '--from-model' => 'StdOut\\SimpleDataObjects\\Tests\\Fixtures\\NoSuchModelAtAll',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('does not exist', Artisan::output());
        $this->assertFileDoesNotExist(app_path('Data/MissingModelData.php'));
    }

    public function test_unknown_table_fails_without_writing_a_file(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('test_products')
            ->andReturn(false);

        $exit = Artisan::call('make:data', [
            'name' => 'NoTableData',
            '--from-model' => TestProductModel::class,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('does not exist', Artisan::output());
        $this->assertFileDoesNotExist(app_path('Data/NoTableData.php'));
    }

    public function test_falls_back_to_data_namespace_when_neither_path_option_nor_configured_paths_are_set(): void
    {
        config(['simple-data-objects.paths' => []]);

        $exit = Artisan::call('make:data', ['name' => 'FallbackData']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('namespace App\\Data;', file_get_contents(app_path('Data/FallbackData.php')));
    }

    public function test_path_option_changes_namespace_and_output_directory(): void
    {
        $exit = Artisan::call('make:data', [
            'name' => 'DtoInCustomDir',
            '--path' => app_path('Dto'),
        ]);

        $this->assertSame(0, $exit);

        $path = app_path('Dto/DtoInCustomDir.php');
        $this->assertFileExists($path);
        $this->assertStringContainsString('namespace App\\Dto;', file_get_contents($path));
    }

    private function seedOrder(): int
    {
        Schema::create('generator_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number');
            $table->boolean('paid');
            $table->float('total');
            $table->string('secret');
        });

        Schema::create('generator_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('sku');
            $table->integer('qty');
        });

        $order = GeneratorOrderModel::create(['order_number' => 'A-1', 'paid' => true, 'total' => 10.0, 'secret' => 's']);
        $order->lines()->createMany([['sku' => 'X', 'qty' => 2], ['sku' => 'Y', 'qty' => 1]]);

        return $order->id;
    }

    public function test_from_model_with_id_generates_from_the_record_and_its_loaded_relations(): void
    {
        $id = $this->seedOrder();

        $exit = Artisan::call('make:data', ['name' => 'OrderData', '--from-model' => GeneratorOrderModel::class, '--id' => $id, '--with' => 'lines']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('created successfully', Artisan::output());

        $order = file_get_contents(app_path('Data/OrderData.php'));
        $this->assertStringContainsString('namespace App\\Data;', $order);
        $this->assertStringContainsString('#[TransformKeys(TransformKeys::SNAKE_CASE)]', $order);
        $this->assertStringContainsString('public readonly bool $paid,', $order);
        $this->assertStringContainsString('public readonly float $total,', $order);
        $this->assertStringContainsString("#[DataCollection(LineData::class)]\n        public readonly TypedDataCollection \$lines,", $order);
        $this->assertStringNotContainsString('secret', $order);
        $this->assertStringContainsString('public readonly int $orderId,', file_get_contents(app_path('Data/LineData.php')));
    }

    public function test_from_model_with_id_reports_a_missing_record_or_model(): void
    {
        $this->seedOrder();

        $this->assertSame(1, Artisan::call('make:data', ['name' => 'OrderData', '--from-model' => GeneratorOrderModel::class, '--id' => 999]));
        $this->assertStringContainsString('No query results', Artisan::output());

        $this->assertSame(1, Artisan::call('make:data', ['name' => 'OrderData', '--from-model' => 'NoSuchModelAtAll', '--id' => 1]));
        $this->assertStringContainsString('does not exist', Artisan::output());

        $this->assertSame(1, Artisan::call('make:data', ['name' => 'OrderData', '--id' => 1]));
        $this->assertStringContainsString('--id requires --from-model.', Artisan::output());
        $this->assertFileDoesNotExist(app_path('Data/OrderData.php'));
    }

    public function test_from_json_and_from_xml_generate_nested_classes(): void
    {
        $dir = sys_get_temp_dir().'/sdo-make-'.bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents("{$dir}/s.json", '{"id": 1, "customer": {"name": "Ada"}, "note": "x"}');
        file_put_contents("{$dir}/s.xml", '<feed><item id="1"><name>a</name></item><item id="2"/></feed>');

        try {
            $this->assertSame(0, Artisan::call('make:data', ['name' => 'InvoiceData', '--from-json' => "{$dir}/s.json", '--optional' => true]));
            $this->assertStringContainsString('public readonly CustomerData $customer,', file_get_contents(app_path('Data/InvoiceData.php')));

            $this->assertSame(0, Artisan::call('make:data', ['name' => 'FeedItemData', '--from-xml' => "{$dir}/s.xml", '--xml-path' => 'feed/item', '--optional' => true]));
            $this->assertStringContainsString('public readonly string|Optional $name,', file_get_contents(app_path('Data/FeedItemData.php')));

            $this->assertSame(1, Artisan::call('make:data', ['name' => 'InvoiceData', '--from-json' => "{$dir}/s.json"]));
            $this->assertStringContainsString('Already exists, pass --force to overwrite', Artisan::output());
            $this->assertSame(0, Artisan::call('make:data', ['name' => 'InvoiceData', '--from-json' => "{$dir}/s.json", '--force' => true]));

            $this->assertSame(1, Artisan::call('make:data', ['name' => 'InvoiceData', '--from-json' => "{$dir}/missing.json"]));
            $this->assertStringContainsString('Cannot read sample file', Artisan::output());
        } finally {
            array_map(unlink(...), glob("{$dir}/*") ?: []);
            rmdir($dir);
        }
    }
}
