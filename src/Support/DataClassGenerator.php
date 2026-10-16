<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;

final class DataClassGenerator
{
    private const string ATTRIBUTES = 'StdOut\\SimpleDataObjects\\Attributes\\';

    private const array SCALAR_ORDER = ['int', 'float', 'string', 'bool'];

    /** @var array<string, string> class name => source */
    private array $classes = [];

    /** @var array<string, string> class name => body used to detect identical shapes */
    private array $bodies = [];

    private function __construct(
        private readonly string $namespace,
        private readonly bool $xml,
        private readonly bool $optional,
    ) {}

    /** @return array<string, string> class name => PHP source */
    public static function fromValue(mixed $value, string $class, string $namespace, bool $optional = false): array
    {
        try {
            $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("The sample cannot be encoded as JSON: {$e->getMessage()}.", 0, $e);
        }

        return self::generate(SampleReader::fromJson($json), $class, $namespace, optional: $optional);
    }

    /**
     * @param  array<string, string>  $classes
     * @return list<string> the written files
     */
    public static function write(array $classes, string $directory, bool $force = false): array
    {
        $paths = [];

        foreach (array_keys($classes) as $name) {
            $paths[$name] = rtrim($directory, '/')."/{$name}.php";
        }

        $existing = array_filter($paths, is_file(...));

        if ($existing !== [] && ! $force) {
            throw new InvalidArgumentException('Already exists, pass --force to overwrite: '.implode(', ', $existing));
        }

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new InvalidArgumentException("Could not create directory: {$directory}");
        }

        foreach ($paths as $name => $path) {
            if (@file_put_contents($path, $classes[$name]) === false) {
                throw new InvalidArgumentException("Could not write {$path}");
            }
        }

        return array_values($paths);
    }

    /**
     * @internal
     *
     * @return array<string, string> class name => PHP source
     */
    public static function generate(SampleType $sample, string $class, string $namespace, bool $xml = false, bool $optional = false): array
    {
        $object = $sample->object ?? ($sample->list ? $sample->items?->object : null);

        if ($object === null || $object->fields === []) {
            throw new InvalidArgumentException('The sample must be an object or a list of objects with at least one field.');
        }

        $generator = new self($namespace, $xml, $optional);
        $generator->bodies[$class] = '';
        $generator->classFor($object, $class, '', true);

        return $generator->classes;
    }

    public static function namespaceFor(string $composerFile, string $directory): ?string
    {
        $composer = is_file($composerFile) ? json_decode((string) file_get_contents($composerFile), true) : null;

        if (! is_array($composer)) {
            return null;
        }

        $base = dirname((string) realpath($composerFile));
        $target = rtrim((string) preg_replace('#/(\./)+#', '/', str_starts_with($directory, '/') ? $directory : getcwd().'/'.$directory), '/');
        $best = null;
        $bestLength = -1;

        foreach ([...$composer['autoload']['psr-4'] ?? [], ...$composer['autoload-dev']['psr-4'] ?? []] as $prefix => $paths) {
            foreach ((array) $paths as $path) {
                $root = rtrim($base.'/'.trim((string) $path, '/'), '/');

                if (($target === $root || str_starts_with($target, $root.'/')) && strlen($root) > $bestLength) {
                    $rest = trim(substr($target, strlen($root)), '/');
                    $best = trim($prefix.str_replace('/', '\\', $rest), '\\');
                    $bestLength = strlen($root);
                }
            }
        }

        return $best;
    }

    private function classFor(SampleObject $object, string $name, string $parent, bool $root = false): string
    {
        [$uses, $classAttributes, $properties] = $this->members($object, $name);
        $body = implode("\n", [...$uses, ...$classAttributes, ...$properties]);
        $stem = substr($name, 0, -4);

        for ($i = 0; ; $i++) {
            $candidate = match (true) {
                $root || $i === 0 => $name,
                $i === 1 => $parent.$name,
                default => "{$parent}{$stem}{$i}Data",
            };

            if ($root || ! isset($this->bodies[$candidate])) {
                $this->bodies[$candidate] = $body;
                $this->classes[$candidate] = $this->render($candidate, $uses, $classAttributes, $properties);

                return $candidate;
            }

            if ($this->bodies[$candidate] === $body) {
                return $candidate;
            }
        }
    }

    /** @return array{0: list<string>, 1: list<string>, 2: list<string>} use statements, class attributes, constructor parameters */
    private function members(SampleObject $object, string $class): array
    {
        $uses = ['StdOut\\SimpleDataObjects\\BaseData' => true];
        $parent = substr($class, 0, -4);
        $required = [];
        $optional = [];
        $renamed = [];
        $taken = [];

        foreach ($object->fields as $key => $type) {
            [$source, $raw] = match (true) {
                $this->xml && $key[0] === '@' => ['attribute', substr($key, 1)],
                $this->xml && $key === '#text' => ['text', 'value'],
                default => ['element', $key],
            };
            $repeated = $this->xml && $source === 'element' && $type->list;
            $effective = $this->xml && $source === 'element' && ! $type->list ? $type->items : $type;
            $property = self::unique(self::propertyName($repeated ? Str::plural($raw) : $raw), $taken);
            $attributes = [];

            [$phpType, $nullable] = $this->phpType($effective ?? new SampleType, $raw, $parent, $attributes, $uses);

            if ($this->xml) {
                $mapping = match ($source) {
                    'attribute' => $raw === $property ? 'XmlAttribute' : 'XmlAttribute('.var_export($raw, true).')',
                    'text' => 'XmlText',
                    default => $raw === $property ? null : 'XmlElement('.var_export($raw, true).')',
                };

                if ($mapping !== null) {
                    $attributes[] = $mapping;
                    $uses[self::ATTRIBUTES.strtok($mapping, '(')] = true;
                }
            } elseif ($raw !== $property) {
                $renamed[$property] = $raw;
            }

            $absent = $object->presence[$key] < $object->seen && ! ($this->xml && $type->list);
            $missing = $absent && $this->optional && $phpType !== 'mixed';
            $defaulted = $absent && ! $missing;

            if ($missing) {
                $phpType .= '|Optional';
                $uses['StdOut\\SimpleDataObjects\\Optional'] = true;
            }

            if ($defaulted || $nullable) {
                $phpType = match (true) {
                    $phpType === 'mixed' => $phpType,
                    str_contains($phpType, '|') => $phpType.'|null',
                    default => '?'.$phpType,
                };
            }

            $line = "public readonly {$phpType} \${$property}".($defaulted ? ' = null' : '').',';
            $entry = [$property, $attributes, $line];

            if ($defaulted) {
                $optional[] = $entry;
            } else {
                $required[] = $entry;
            }
        }

        $classAttributes = [];
        $snake = $renamed !== [] && array_all($renamed, static fn (string $raw, string $property): bool => KeyTransformer::apply($property, TransformKeys::SNAKE_CASE) === $raw);

        if ($snake) {
            $classAttributes[] = '#[TransformKeys(TransformKeys::SNAKE_CASE)]';
            $uses[self::ATTRIBUTES.'TransformKeys'] = true;
        } elseif ($renamed !== []) {
            $uses[self::ATTRIBUTES.'MapPropertyName'] = true;
        }

        $properties = [];

        foreach ([...$required, ...$optional] as [$property, $attributes, $line]) {
            if (! $snake && isset($renamed[$property])) {
                array_unshift($attributes, 'MapPropertyName('.var_export($renamed[$property], true).')');
            }

            foreach ($attributes as $attribute) {
                $properties[] = "        #[{$attribute}]";
            }

            $properties[] = "        {$line}";
        }

        $useLines = array_keys($uses);
        sort($useLines);

        return [array_map(static fn (string $use): string => "use {$use};", $useLines), $classAttributes, $properties];
    }

    /**
     * @param  list<string>  $attributes
     * @param  array<string, true>  $uses
     * @return array{0: string, 1: bool} type without nullability, whether null was seen
     */
    private function phpType(SampleType $type, string $raw, string $parent, array &$attributes, array &$uses): array
    {
        $kinds = ($type->scalars !== [] ? 1 : 0) + ($type->object !== null ? 1 : 0) + ($type->list ? 1 : 0);

        if ($kinds !== 1) {
            return ['mixed', false];
        }

        if ($type->object !== null) {
            return $type->object->fields === []
                ? ['array', $type->nullable]
                : [$this->classFor($type->object, Str::studly($raw).'Data', $parent), $type->nullable];
        }

        $items = $type->items;

        if ($type->list && $items !== null && $items->object !== null && $items->object->fields !== [] && $items->scalars === [] && ! $items->list) {
            $class = $this->classFor($items->object, Str::studly(Str::singular($raw)).'Data', $parent);
            $attributes[] = "DataCollection({$class}::class)";
            $uses[self::ATTRIBUTES.'DataCollection'] = true;
            $uses['StdOut\\SimpleDataObjects\\TypedDataCollection'] = true;

            return ['TypedDataCollection', $type->nullable];
        }

        if ($type->list) {
            return ['array', $type->nullable];
        }

        $scalars = $type->scalars;

        if (isset($scalars['int'], $scalars['float'])) {
            unset($scalars['int']);
        }

        if ($this->xml && isset($scalars['string'])) {
            $scalars = ['string' => true];
        }

        return [implode('|', array_values(array_filter(self::SCALAR_ORDER, static fn (string $scalar): bool => isset($scalars[$scalar])))), $type->nullable];
    }

    private static function propertyName(string $raw): string
    {
        $name = Str::camel((string) preg_replace('/[^A-Za-z0-9]+/', '_', $raw));

        return match (true) {
            $name === '' => 'field',
            ctype_digit($name[0]) => 'field'.$name,
            $name === 'this' => 'this_',
            default => $name,
        };
    }

    /** @param array<string, true> $taken */
    private static function unique(string $name, array &$taken): string
    {
        $candidate = $name;

        for ($i = 2; isset($taken[$candidate]); $i++) {
            $candidate = $name.$i;
        }

        $taken[$candidate] = true;

        return $candidate;
    }

    /**
     * @param  list<string>  $uses
     * @param  list<string>  $classAttributes
     * @param  list<string>  $properties
     */
    private function render(string $class, array $uses, array $classAttributes, array $properties): string
    {
        $header = $classAttributes === [] ? '' : implode("\n", $classAttributes)."\n";

        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$this->namespace};\n\n"
            .implode("\n", $uses)."\n\n"
            ."{$header}class {$class} extends BaseData\n{\n    public function __construct(\n"
            .implode("\n", $properties)."\n    ) {}\n}\n";
    }
}
