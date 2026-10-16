<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use StdOut\SimpleDataObjects\Support\DataClassGenerator;
use StdOut\SimpleDataObjects\Support\SampleReader;
use Symfony\Component\Console\Input\InputOption;

/**
 * `make:data OrderData` — stub generator. With `--from-model=Order`, reads
 * the model's table columns via `Schema::getColumns()` (Laravel 11+,
 * database-agnostic) and generates typed constructor properties; this is
 * dev-time codegen, not the hydration hot path, so reflection/DB
 * introspection are used freely here.
 */
final class MakeDataCommand extends GeneratorCommand
{
    protected $name = 'make:data';

    protected $description = 'Create a new data object class';

    protected $type = 'Data object';

    /**
     * Maps a normalized `type_name` from Schema::getColumns() to a
     * [php type, validation rule] pair. Best-effort — unmatched types fall
     * back to `mixed` with no rule rather than guessing wrong.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array TYPE_MAP = [
        'varchar' => ['string', 'string'],
        'char' => ['string', 'string'],
        'text' => ['string', 'string'],
        'string' => ['string', 'string'],
        'int' => ['int', 'integer'],
        'integer' => ['int', 'integer'],
        'bigint' => ['int', 'integer'],
        'smallint' => ['int', 'integer'],
        'tinyint' => ['int', 'integer'],
        'bool' => ['bool', 'boolean'],
        'boolean' => ['bool', 'boolean'],
        'float' => ['float', 'numeric'],
        'double' => ['float', 'numeric'],
        'decimal' => ['float', 'numeric'],
        'numeric' => ['float', 'numeric'],
        'date' => ['string', 'date'],
        'datetime' => ['string', 'date'],
        'timestamp' => ['string', 'date'],
        'json' => ['array', 'array'],
        'jsonb' => ['array', 'array'],
    ];

    /**
     * Resolved once in handle() and reused by propertiesBlock() — also lets
     * an unresolvable --from-model abort with a real failure exit code
     * instead of silently falling back to the bare-stub TODO placeholder.
     *
     * @var list<array{name: string, type_name: string, nullable: bool}>|null
     */
    private ?array $resolvedColumns = null;

    public function handle()
    {
        if ($this->option('from-json') !== null || $this->option('from-xml') !== null || $this->option('id') !== null) {
            return $this->generateFromSample();
        }

        if ($this->option('rules') && $this->option('from-model') === null) {
            $this->components->warn('--rules has no effect without --from-model; ignoring.');
        }

        $model = $this->option('from-model');

        if ($model !== null) {
            $this->resolvedColumns = $this->columnsForModel($model);

            if ($this->resolvedColumns === null) {
                return self::FAILURE;
            }
        }

        return parent::handle();
    }

    protected function getStub(): string
    {
        return __DIR__.'/stubs/data.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\'.$this->relativeDataNamespace();
    }

    protected function getOptions(): array
    {
        return [
            ['from-model', null, InputOption::VALUE_OPTIONAL, "Generate typed properties from a model's database columns"],
            ['rules', null, InputOption::VALUE_NONE, '#[Rules] attributes inferred from column types (requires --from-model)'],
            ['collection', null, InputOption::VALUE_NONE, 'Add a doc-comment pointing at static::collection()'],
            ['path', null, InputOption::VALUE_OPTIONAL, "Output directory, relative to the app's Models convention (defaults to config('simple-data-objects.paths')[0])"],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if it already exists'],
            ['from-json', null, InputOption::VALUE_REQUIRED, 'Generate the class and its nested classes from a sample JSON file'],
            ['from-xml', null, InputOption::VALUE_REQUIRED, 'Generate the class and its nested classes from a sample XML file'],
            ['xml-path', null, InputOption::VALUE_REQUIRED, 'Path to the record element in --from-xml (default: the repeated child of the root)'],
            ['id', null, InputOption::VALUE_REQUIRED, 'With --from-model: generate from this record instead of the table columns'],
            ['with', null, InputOption::VALUE_REQUIRED, 'With --id: comma-separated relations to load and generate nested classes for'],
            ['optional', null, InputOption::VALUE_NONE, 'Keys missing from some sample objects become T|Optional instead of ?T = null'],
        ];
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $stub = str_replace('{{ imports }}', $this->importsBlock(), $stub);
        $stub = str_replace('{{ classDoc }}', $this->classDocBlock(), $stub);

        return str_replace('{{ properties }}', $this->propertiesBlock(), $stub);
    }

    private function generateFromSample(): int
    {
        $qualified = $this->qualifyClass($this->getNameInput());
        $class = class_basename($qualified);
        $namespace = Str::beforeLast($qualified, '\\');
        $optional = (bool) $this->option('optional');

        try {
            $classes = match (true) {
                $this->option('from-xml') !== null => DataClassGenerator::generate(SampleReader::fromXml($this->option('from-xml'), $this->option('xml-path'))[0], $class, $namespace, true, $optional),
                $this->option('from-json') !== null => DataClassGenerator::generate(SampleReader::fromJson($this->sampleFile($this->option('from-json'))), $class, $namespace, optional: $optional),
                default => DataClassGenerator::fromValue($this->record(), $class, $namespace, $optional),
            };

            $written = DataClassGenerator::write($classes, dirname($this->getPath($qualified)), (bool) $this->option('force'));
        } catch (InvalidArgumentException|ModelNotFoundException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($written as $path) {
            $this->components->info("Data object [{$path}] created successfully.");
        }

        return self::SUCCESS;
    }

    private function sampleFile(string $path): string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new InvalidArgumentException("Cannot read sample file: {$path}");
        }

        return $contents;
    }

    private function record(): Model
    {
        $model = $this->option('from-model');

        if ($model === null) {
            throw new InvalidArgumentException('--id requires --from-model.');
        }

        $class = class_exists($model) ? $model : $this->qualifyModel($model);

        if (! is_subclass_of($class, Model::class)) {
            throw new InvalidArgumentException("Model class [{$class}] does not exist.");
        }

        return $class::with(array_filter(explode(',', (string) $this->option('with'))))->findOrFail($this->option('id'));
    }

    private function relativeDataNamespace(): string
    {
        $configured = $this->option('path') ?? (config('simple-data-objects.paths')[0] ?? null);

        if ($configured === null) {
            return 'Data';
        }

        $relative = trim(str_replace(app_path(), '', $configured), '/\\');
        $relative = str_replace('/', '\\', $relative);

        return $relative !== '' ? $relative : 'Data';
    }

    private function importsBlock(): string
    {
        $imports = [];

        if ($this->option('from-model') !== null) {
            // Columns are snake_case; properties below are camelCased, so
            // hydration from the model's raw attributes needs the class-level
            // strategy rather than per-property #[MapPropertyName].
            $imports[] = 'use StdOut\\SimpleDataObjects\\Attributes\\TransformKeys;';
        }

        if ($this->usesRules()) {
            $imports[] = 'use StdOut\\SimpleDataObjects\\Attributes\\Rules;';
        }

        return $imports === [] ? '' : implode("\n", $imports)."\n";
    }

    private function classDocBlock(): string
    {
        $lines = [];

        if ($this->option('collection')) {
            $lines[] = "/**\n * @see static::collection() to hydrate a list, e.g. static::collection(\$rows).\n */";
        }

        if ($this->option('from-model') !== null) {
            $lines[] = '#[TransformKeys(TransformKeys::SNAKE_CASE)]';
        }

        return $lines === [] ? '' : implode("\n", $lines)."\n";
    }

    private function usesRules(): bool
    {
        return (bool) $this->option('rules') && $this->option('from-model') !== null;
    }

    private function propertiesBlock(): string
    {
        if ($this->resolvedColumns === null) {
            return '';
        }

        return implode("\n\n", array_map(
            fn (array $column): string => $this->propertyLine($column),
            $this->resolvedColumns,
        ));
    }

    /**
     * @return list<array{name: string, type_name: string, nullable: bool, auto_increment: bool, generation: array<string, mixed>|null}>|null
     */
    private function columnsForModel(string $model): ?array
    {
        // Accept an already-fully-qualified, existing class name as-is;
        // qualifyModel() otherwise assumes a bare name relative to the
        // app's root namespace (optionally under Models/), which is right
        // for the common case but wrong for models living elsewhere.
        $class = class_exists($model) ? $model : $this->qualifyModel($model);

        if (! class_exists($class)) {
            $this->components->error("Model class [{$class}] does not exist.");

            return null;
        }

        $table = (new $class)->getTable();

        if (! Schema::hasTable($table)) {
            $this->components->error("Table [{$table}] for model [{$class}] does not exist.");

            return null;
        }

        return array_values(array_filter(
            Schema::getColumns($table),
            static fn (array $column): bool => ! $column['auto_increment'] && $column['generation'] === null,
        ));
    }

    /** @param  array{name: string, type_name: string, nullable: bool}  $column */
    private function propertyLine(array $column): string
    {
        [$phpType, $rule] = self::TYPE_MAP[$column['type_name']] ?? ['mixed', null];

        $nullable = $column['nullable'];
        $type = $nullable && $phpType !== 'mixed' ? '?'.$phpType : $phpType;
        $variable = Str::camel($column['name']);

        $attribute = '';

        if ($this->usesRules()) {
            $ruleList = $rule !== null
                ? sprintf("'%s', '%s'", $nullable ? 'nullable' : 'required', $rule)
                : sprintf("'%s'", $nullable ? 'nullable' : 'required');

            $attribute = "        #[Rules([{$ruleList}])]\n";
        }

        $default = $nullable ? ' = null' : '';

        return "{$attribute}        public readonly {$type} \${$variable}{$default},";
    }
}
