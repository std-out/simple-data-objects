<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\DataClassGenerator;
use StdOut\SimpleDataObjects\Support\SampleReader;

class DataClassGeneratorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/sdo-generate-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->dir.'/*') ?: []);
        rmdir($this->dir);
    }

    /** @param array<string, string> $classes */
    private function load(array $classes): void
    {
        foreach ($classes as $name => $source) {
            file_put_contents("{$this->dir}/{$name}.php", $source);
            require "{$this->dir}/{$name}.php";
        }
    }

    private function namespace(): string
    {
        return 'Generated\\N'.bin2hex(random_bytes(6));
    }

    /** @return array<string, string> */
    private function fromJson(string $json, string $class, bool $optional = false, ?string $namespace = null): array
    {
        return DataClassGenerator::generate(SampleReader::fromJson($json), $class, $namespace ?? $this->namespace(), optional: $optional);
    }

    private function xmlFile(string $xml): string
    {
        file_put_contents($path = $this->dir.'/sample.xml', $xml);

        return $path;
    }

    private const string ORDER = <<<'JSON'
        {
          "id": 1042,
          "order_number": "A-1042",
          "total": 199.9,
          "paid": true,
          "tags": ["vip", "gift"],
          "customer": {"first_name": "Ada", "address": {"city": "Kyiv"}},
          "items": [
            {"sku": "A1", "price": 10, "options": {"color": "red"}},
            {"sku": "B2", "price": 9.5, "discount": 1.5}
          ]
        }
        JSON;

    public function test_json_sample_generates_nested_classes_and_collections(): void
    {
        $ns = $this->namespace();
        $classes = $this->fromJson(self::ORDER, 'OrderData', namespace: $ns);

        $this->assertSame(['AddressData', 'CustomerData', 'OptionsData', 'ItemData', 'OrderData'], array_keys($classes));
        $this->assertStringContainsString("namespace {$ns};", $classes['OrderData']);
        $this->assertStringContainsString("#[TransformKeys(TransformKeys::SNAKE_CASE)]\nclass OrderData extends BaseData", $classes['OrderData']);
        $this->assertStringContainsString('public readonly string $orderNumber,', $classes['OrderData']);
        $this->assertStringContainsString("#[DataCollection(ItemData::class)]\n        public readonly TypedDataCollection \$items,", $classes['OrderData']);
        $this->assertStringContainsString('public readonly array $tags,', $classes['OrderData']);
        $this->assertStringContainsString("#[TransformKeys(TransformKeys::SNAKE_CASE)]\nclass CustomerData extends BaseData", $classes['CustomerData']);
        $this->assertStringContainsString("        public readonly float \$price,\n        public readonly ?OptionsData \$options = null,\n        public readonly ?float \$discount = null,", $classes['ItemData']);

        $this->load($classes);
        $order = ("{$ns}\\OrderData")::from(json_decode(self::ORDER, true));

        $this->assertSame('A-1042', $order->orderNumber);
        $this->assertSame('Kyiv', $order->customer->address->city);
        $this->assertSame('red', $order->items->first()->options->color);
        $this->assertNull($order->items->all()[1]->options);
        $this->assertSame(1.5, $order->items->all()[1]->discount);
    }

    public function test_optional_mode_round_trips_the_sample_exactly(): void
    {
        $ns = $this->namespace();
        $classes = $this->fromJson(self::ORDER, 'OrderData', optional: true, namespace: $ns);

        $this->assertStringContainsString("        public readonly OptionsData|Optional \$options,\n        public readonly float|Optional \$discount,", $classes['ItemData']);
        $this->assertStringContainsString('use StdOut\\SimpleDataObjects\\Optional;', $classes['ItemData']);

        $this->load($classes);
        $sample = json_decode(self::ORDER, true);

        $this->assertSame(json_encode($sample), ("{$ns}\\OrderData")::from($sample)->toJson());
    }

    public function test_types_are_inferred_across_every_object_of_a_list(): void
    {
        $classes = $this->fromJson('[
            {"n": 1, "m": 1, "s": "a", "o": {"x": 1}, "p": {"x": 1}, "u": null, "e": {}, "l": [1, "a"], "mixed": [{"x": 1}, 2], "q": 1},
            {"n": "a", "m": 1.5, "s": null, "o": null, "p": 5, "u": null, "e": {}, "l": [], "mixed": [], "z": null}
        ]', 'RowData', optional: true);

        $row = $classes['RowData'];

        $this->assertStringContainsString('public readonly int|string $n,', $row);
        $this->assertStringContainsString('public readonly float $m,', $row);
        $this->assertStringContainsString('public readonly ?string $s,', $row);
        $this->assertStringContainsString('public readonly ?OData $o,', $row);
        $this->assertStringContainsString('public readonly mixed $p,', $row);
        $this->assertStringContainsString('public readonly mixed $u,', $row);
        $this->assertStringContainsString('public readonly array $e,', $row);
        $this->assertStringContainsString('public readonly array $l,', $row);
        $this->assertStringContainsString('public readonly array $mixed,', $row);
        $this->assertStringContainsString('public readonly int|Optional $q,', $row);
        $this->assertStringContainsString('public readonly mixed $z = null,', $row);
    }

    public function test_awkward_keys_get_valid_unique_property_names(): void
    {
        $ns = $this->namespace();
        $sample = '{"user_id": 1, "userId": 2, "2fa": true, "--": "x", "this": "t", "created-at": "now"}';
        $classes = $this->fromJson($sample, 'KeysData', namespace: $ns);
        $source = $classes['KeysData'];

        $this->assertStringNotContainsString('TransformKeys', $source);

        foreach (["#[MapPropertyName('user_id')]\n        public readonly int \$userId,", "#[MapPropertyName('userId')]\n        public readonly int \$userId2,", "#[MapPropertyName('2fa')]\n        public readonly bool \$field2fa,", "#[MapPropertyName('--')]\n        public readonly string \$field,", "#[MapPropertyName('this')]\n        public readonly string \$this_,", "#[MapPropertyName('created-at')]\n        public readonly string \$createdAt,"] as $expected) {
            $this->assertStringContainsString($expected, $source);
        }

        $this->load($classes);

        $this->assertSame(json_decode($sample, true), ("{$ns}\\KeysData")::from(json_decode($sample, true))->toArray());
    }

    public function test_identical_shapes_share_a_class_and_different_ones_are_renamed(): void
    {
        $classes = $this->fromJson('{
            "billing": {"address": {"city": "K"}},
            "shipping": {"address": {"city": "L"}},
            "a": {"billing": {"address": {"zip": 1}}},
            "b": {"billing": {"address": {"street": "s"}}}
        }', 'RootData');

        $this->assertSame(['AddressData', 'BillingData', 'ShippingData', 'BillingAddressData', 'ABillingData', 'AData', 'BillingAddress2Data', 'BBillingData', 'BData', 'RootData'], array_keys($classes));
        $this->assertStringContainsString('public readonly AddressData $address,', $classes['ShippingData']);
        $this->assertStringContainsString('public readonly BillingAddress2Data $address,', $classes['BBillingData']);
    }

    public function test_root_class_keeps_its_name_when_a_nested_key_matches_it(): void
    {
        $classes = $this->fromJson('{"order": {"x": 1}}', 'OrderData');

        $this->assertSame(['OrderOrderData', 'OrderData'], array_keys($classes));
        $this->assertStringContainsString('public readonly OrderOrderData $order,', $classes['OrderData']);
    }

    public function test_sample_must_contain_an_object(): void
    {
        foreach (['42', '[1, 2]', '{}', '[]'] as $json) {
            try {
                $this->fromJson($json, 'XData');
                $this->fail("Expected an exception for {$json}");
            } catch (InvalidArgumentException $e) {
                $this->assertSame('The sample must be an object or a list of objects with at least one field.', $e->getMessage());
            }
        }
    }

    public function test_invalid_json_is_reported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON sample: Syntax error.');

        SampleReader::fromJson('{"a": ');
    }

    private const string CATALOG = <<<'XML'
        <?xml version="1.0"?>
        <catalog xmlns:g="http://base.google.com/ns/1.0">
          <offer id="1" available="true">
            <name>Phone</name>
            <price currency="UAH">999.50</price>
            <stock>3</stock>
            <code>7</code>
            <g:brand>Acme</g:brand>
            <picture>a.jpg</picture>
            <picture>b.jpg</picture>
            <param name="Color">Red</param>
            <param name="Size">L</param>
            <delivery><option cost="0"/></delivery>
            <label kind="new">Hot</label>
          </offer>
          <offer id="2" available="false">
            <name>Case</name>
            <price currency="UAH">99</price>
            <stock/>
            <code>A7</code>
            <g:brand>Acme</g:brand>
            <picture>c.jpg</picture>
            <param name="Color">Blue</param>
            <delivery><option cost="50"/><option cost="0"/></delivery>
            <label kind="old"/>
          </offer>
        </catalog>
        XML;

    public function test_xml_sample_generates_classes_that_lazy_xml_hydrates(): void
    {
        $file = $this->xmlFile(self::CATALOG);
        [$sample, $path] = SampleReader::fromXml($file);

        $this->assertSame('catalog/offer', $path);

        $ns = $this->namespace();
        $classes = DataClassGenerator::generate($sample, 'OfferData', $ns, xml: true);
        $offer = $classes['OfferData'];

        foreach (["#[XmlAttribute]\n        public readonly int \$id,", "#[XmlAttribute]\n        public readonly bool \$available,", 'public readonly ?int $stock,', 'public readonly string $code,', "#[XmlElement('g:brand')]\n        public readonly string \$gBrand,", "#[XmlElement('picture')]\n        public readonly array \$pictures,", "#[DataCollection(ParamData::class)]\n        #[XmlElement('param')]\n        public readonly TypedDataCollection \$params,"] as $expected) {
            $this->assertStringContainsString($expected, $offer);
        }

        $this->assertStringContainsString("#[XmlText]\n        public readonly float \$value,", $classes['PriceData']);
        $this->assertStringContainsString("#[XmlText]\n        public readonly ?string \$value,", $classes['LabelData']);

        $this->load($classes);
        $rows = iterator_to_array(("{$ns}\\OfferData")::lazyXml($file, $path), false);

        $this->assertSame(999.5, $rows[0]->price->value);
        $this->assertSame(['a.jpg', 'b.jpg'], $rows[0]->pictures);
        $this->assertSame('Blue', $rows[1]->params->first()->value);
        $this->assertNull($rows[1]->stock);
        $this->assertCount(2, $rows[1]->delivery->options);
        $this->assertNull($rows[1]->label->value);
    }

    public function test_xml_attribute_named_unlike_its_property_is_mapped(): void
    {
        [$sample] = SampleReader::fromXml($this->xmlFile('<item data-id="5"><name>x</name></item>'));
        $classes = DataClassGenerator::generate($sample, 'ItemData', 'App', xml: true);

        $this->assertStringContainsString("#[XmlAttribute('data-id')]\n        public readonly int \$dataId,", $classes['ItemData']);
    }

    public function test_xml_path_defaults_to_the_root_without_repeated_children(): void
    {
        $file = $this->xmlFile('<order id="1"><item>a</item><note>b</note></order>');

        $this->assertSame('order', SampleReader::fromXml($file)[1]);
        $this->assertSame('/order/', SampleReader::fromXml($file, '/order/')[1]);

        [$sample] = SampleReader::fromXml($this->xmlFile('<r><meta><x/></meta><i a="1"/><i a="2"/></r>'), 'r/i');
        $this->assertSame(['@a'], array_keys($sample->object->fields ?? []));
    }

    public function test_xml_errors_are_reported(): void
    {
        $cases = [
            ['<r><i>1</i><i>2</i></r>', null, 'No element with attributes or child elements found at "r/i" in '],
            ['<r><i a="1"></r>', 'r/i', 'Invalid XML sample '],
            ['<r><i a="1"></r>', null, 'Invalid XML sample '],
            ['<?xml version="1.0"?>', null, 'Invalid XML sample '],
        ];

        foreach ($cases as [$xml, $path, $message]) {
            try {
                SampleReader::fromXml($this->xmlFile($xml), $path);
                $this->fail("Expected an exception for {$xml}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringStartsWith($message, $e->getMessage());
            }
        }

        $this->expectExceptionMessage('Cannot read XML sample ');
        SampleReader::fromXml('');
    }

    public function test_namespace_is_derived_from_the_psr4_map(): void
    {
        $composer = $this->dir.'/composer.json';
        file_put_contents($composer, json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/', 'App\\Domain\\' => ['src/domain', 'lib/domain']]],
            'autoload-dev' => ['psr-4' => ['Tests\\' => 'tests/']],
        ]));

        $this->assertSame('App\\Data\\Orders', DataClassGenerator::namespaceFor($composer, $this->dir.'/app/Data/Orders/'));
        $this->assertSame('App', DataClassGenerator::namespaceFor($composer, $this->dir.'/./app'));
        $this->assertSame('App\\Domain\\Data', DataClassGenerator::namespaceFor($composer, $this->dir.'/lib/domain/Data'));
        $this->assertSame('Tests\\Fixtures', DataClassGenerator::namespaceFor($composer, $this->dir.'/tests/Fixtures'));
        $this->assertNull(DataClassGenerator::namespaceFor($composer, $this->dir.'/application'));
        $this->assertNull(DataClassGenerator::namespaceFor($this->dir.'/missing.json', $this->dir.'/app'));

        $cwd = getcwd();
        chdir($this->dir);

        try {
            $this->assertSame('App\\Data', DataClassGenerator::namespaceFor($composer, 'app/Data'));
        } finally {
            chdir((string) $cwd);
        }

        file_put_contents($composer, 'not json');
        $this->assertNull(DataClassGenerator::namespaceFor($composer, $this->dir.'/app'));
    }

    public function test_from_value_accepts_arrays_and_objects(): void
    {
        $ns = $this->namespace();
        $value = ['id' => 1, 'price' => 10.0, 'owner' => new \ArrayObject(['name' => 'Ada']), 'tags' => ['a']];
        $classes = DataClassGenerator::fromValue($value, 'ThingData', $ns);

        $this->assertStringContainsString('public readonly float $price,', $classes['ThingData']);
        $this->assertStringContainsString('public readonly OwnerData $owner,', $classes['ThingData']);

        $this->load($classes);
        $this->assertSame('Ada', ("{$ns}\\ThingData")::from(['id' => 1, 'price' => 10.0, 'owner' => ['name' => 'Ada'], 'tags' => ['a']])->owner->name);

        $this->expectExceptionMessage('The sample cannot be encoded as JSON: ');
        DataClassGenerator::fromValue(['bad' => NAN], 'XData', $ns);
    }

    public function test_write_creates_files_and_refuses_to_overwrite_without_force(): void
    {
        $classes = ['AData' => '<?php // a', 'BData' => '<?php // b'];
        $target = $this->dir.'/out';

        $this->assertSame(["{$target}/AData.php", "{$target}/BData.php"], DataClassGenerator::write($classes, $target.'/'));
        $this->assertSame('<?php // b', file_get_contents("{$target}/BData.php"));

        try {
            DataClassGenerator::write($classes, $target);
            $this->fail('Expected an exception');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Already exists, pass --force to overwrite: {$target}/AData.php, {$target}/BData.php", $e->getMessage());
        }

        DataClassGenerator::write(['AData' => '<?php // new'], $target, force: true);
        $this->assertSame('<?php // new', file_get_contents("{$target}/AData.php"));

        array_map(unlink(...), glob("{$target}/*") ?: []);
        mkdir("{$target}/CData.php");

        try {
            DataClassGenerator::write(['CData' => 'x'], $target);
            $this->fail('Expected an exception');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Could not write {$target}/CData.php", $e->getMessage());
        } finally {
            rmdir("{$target}/CData.php");
            rmdir($target);
        }

        $this->expectExceptionMessage('Could not create directory: ');
        DataClassGenerator::write($classes, $this->xmlFile('<a/>').'/nested');
    }

    public function test_rendered_class_is_exact(): void
    {
        $classes = $this->fromJson('{"items": [{"x": 1}], "user_name": "a", "n": [{"v": 1}, {"v": "a"}, {"v": null}]}', 'OrderData', namespace: 'App\\Data');

        $this->assertSame(<<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Data;

            use StdOut\SimpleDataObjects\Attributes\DataCollection;
            use StdOut\SimpleDataObjects\Attributes\TransformKeys;
            use StdOut\SimpleDataObjects\BaseData;
            use StdOut\SimpleDataObjects\TypedDataCollection;

            #[TransformKeys(TransformKeys::SNAKE_CASE)]
            class OrderData extends BaseData
            {
                public function __construct(
                    #[DataCollection(ItemData::class)]
                    public readonly TypedDataCollection $items,
                    public readonly string $userName,
                    #[DataCollection(NData::class)]
                    public readonly TypedDataCollection $n,
                ) {}
            }

            PHP, $classes['OrderData']);
        $this->assertStringContainsString('public readonly int|string|null $v,', $classes['NData']);
    }

    public function test_xml_text_is_joined_around_child_elements_and_trimmed(): void
    {
        [$sample] = SampleReader::fromXml($this->xmlFile("<r><p a=\"1\">\n  7<b>x</b>\n</p><p a=\"2\">8<b>y</b></p></r>"));

        $this->assertSame(['int' => true], $sample->object?->fields['#text']->scalars);
        $this->assertSame(2, $sample->object?->seen);
    }

    public function test_xml_path_tracks_levels_across_sibling_groups(): void
    {
        $file = $this->xmlFile('<r><g><i a="1"/></g><skip><g><i a="9"/></g></skip><g><i a="2"/></g></r>');

        $this->assertSame(2, SampleReader::fromXml($file, 'r/g/i')[0]->object?->seen);
    }

    public function test_a_failed_parse_does_not_leak_into_the_next_one(): void
    {
        try {
            SampleReader::fromXml($this->xmlFile('<r><i a="1"></r>'), 'r/i');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame('r/i', SampleReader::fromXml($this->xmlFile('<r><i a="1"/><i a="2"/></r>'))[1]);
    }
}
