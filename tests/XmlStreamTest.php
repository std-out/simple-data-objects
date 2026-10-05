<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\XmlStream;
use StdOut\SimpleDataObjects\Tests\Fixtures\BankPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CardPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConflictXmlSourceData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmailChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\GenericChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\XmlCircleData;
use StdOut\SimpleDataObjects\Tests\Fixtures\XmlExtrasData;
use StdOut\SimpleDataObjects\Tests\Fixtures\XmlOfferData;
use StdOut\SimpleDataObjects\Tests\Fixtures\XmlPriceData;
use StdOut\SimpleDataObjects\Tests\Fixtures\XmlPriority;
use StdOut\SimpleDataObjects\Tests\Fixtures\XmlShapeData;

final class XmlStreamTest extends TestCase
{
    private const string CATALOG = <<<'XML'
        <catalog date="2026-10-04">
            <shop>
                <name>Ignored</name>
                <offers/>
                <offers>
                    <offer id="1" available="true">
                        <name>Kettle</name>
                        <price currency="UAH">499.90</price>
                        <stock>12</stock>
                        <note>Fragile</note>
                        <picture>https://example.com/1a.jpg</picture>
                        <picture>https://example.com/1b.jpg</picture>
                        <param name="Color" weight="5">Red</param>
                        <param name="Volume" weight="">1.7<![CDATA[ L]]></param>
                        <extras>
                            <param name="Warranty">2 years</param>
                            <unknown>skipped</unknown>
                        </extras>
                        <vendor code="BSH"><name>Bosch</name><country>DE</country></vendor>
                        <quantity uom="pcs">3</quantity>
                        <updated>2026-10-01T10:00:00+00:00</updated>
                        <priority>2</priority>
                        <status>inactive</status>
                        <width>10.5</width>
                        <height/>
                        <description><b>Skipped</b> entirely</description>
                    </offer>
                    <offer id="2" available="0">
                        <name>Toaster</name>
                        <price currency="USD">20</price>
                        <stock/>
                        <note/>
                        <vendor code="PHL"/>
                        <updated/>
                    </offer>
                    <offer id="3" available="1"/>
                </offers>
            </shop>
        </catalog>
        XML;

    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/sdo_xml_'.uniqid().'.xml';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function test_it_returns_a_lazy_collection_that_can_be_iterated_repeatedly(): void
    {
        $offers = $this->offers();

        $this->assertInstanceOf(LazyCollection::class, $offers);
        $this->assertCount(2, $offers->take(2));
        $this->assertSame([], $offers->skip(1)->first()->pictures);
        $this->assertCount(0, $offers->skip(1)->first()->params);
        $this->assertSame([1, 2], $offers->take(2)->map(fn (XmlOfferData $offer): int => $offer->id)->all());
    }

    public function test_it_maps_attributes_child_elements_and_typed_scalars(): void
    {
        $offer = $this->offers()->first();

        $this->assertInstanceOf(XmlOfferData::class, $offer);
        $this->assertSame(1, $offer->id);
        $this->assertTrue($offer->available);
        $this->assertSame('Kettle', $offer->name);
        $this->assertSame(12, $offer->stock);
        $this->assertSame('Fragile', $offer->note);
        $this->assertSame(XmlPriority::High, $offer->priority);
        $this->assertSame(Status::Inactive, $offer->status);
        $this->assertSame('2026-10-01', $offer->updated->format('Y-m-d'));
    }

    public function test_it_collects_repeated_elements_into_lists_and_collections(): void
    {
        $offer = $this->offers()->first();

        $this->assertSame(['https://example.com/1a.jpg', 'https://example.com/1b.jpg'], $offer->pictures);

        $this->assertCount(2, $offer->params);
        $this->assertSame('Color', $offer->params[0]->name);
        $this->assertSame('Red', $offer->params[0]->value);
        $this->assertSame(5, $offer->params[0]->weight);
        $this->assertSame('1.7 L', $offer->params[1]->value);
        $this->assertNull($offer->params[1]->weight);
    }

    public function test_every_structured_element_maps_to_its_own_data_object(): void
    {
        $offer = $this->offers()->first();

        $this->assertInstanceOf(XmlPriceData::class, $offer->price);
        $this->assertSame(499.90, $offer->price->amount);
        $this->assertSame('UAH', $offer->price->currency);

        $this->assertInstanceOf(XmlExtrasData::class, $offer->extras);
        $this->assertCount(1, $offer->extras->params);
        $this->assertSame('Warranty', $offer->extras->params[0]->name);
        $this->assertSame('2 years', $offer->extras->params[0]->value);
    }

    public function test_it_hydrates_nested_and_flattened_data_objects(): void
    {
        $offer = $this->offers()->first();

        $this->assertSame('BSH', $offer->vendor->code);
        $this->assertSame('Bosch', $offer->vendor->name);
        $this->assertSame(3, $offer->quantity->amount);
        $this->assertSame('pcs', $offer->quantity->unit);
        $this->assertSame(10.5, $offer->dimensions->width);
        $this->assertNull($offer->dimensions->height);
    }

    public function test_empty_elements_resolve_to_null_for_nullable_fields(): void
    {
        $this->write(self::CATALOG);

        $rows = iterator_to_array(XmlStream::read(XmlOfferData::class, $this->file, 'catalog/shop/offers/offer'));

        $this->assertSame([
            'pictures' => [],
            'params' => [],
            'id' => 2,
            'available' => false,
            'name' => 'Toaster',
            'price' => ['currency' => 'USD', 'amount' => 20.0],
            'stock' => null,
            'note' => null,
            'vendor' => ['code' => 'PHL'],
            'updated' => null,
        ], $rows[1]);
        $this->assertSame(['pictures' => [], 'params' => [], 'id' => 3, 'available' => true], $rows[2]);
    }

    public function test_a_missing_required_element_fails_only_when_that_item_is_reached(): void
    {
        $offers = $this->offers();

        $this->assertSame('Kettle', $offers->first()->name);

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Missing required field 'name'");

        $offers->all();
    }

    public function test_it_accepts_surrounding_slashes_and_a_root_level_path(): void
    {
        $this->write('<user><name>Alice</name><email>alice@example.com</email></user>');

        $users = UserData::lazyXml($this->file, '/user/')->all();

        $this->assertCount(1, $users);
        $this->assertSame('alice@example.com', $users[0]->email);
    }

    public function test_it_yields_nothing_when_the_path_does_not_match(): void
    {
        $this->write(self::CATALOG);

        $this->assertSame([], UserData::lazyXml($this->file, 'catalog/users/user')->all());
    }

    public function test_it_throws_on_xml_truncated_outside_an_item(): void
    {
        $this->write('<users><user><name>Alice</name><email>a@example.com</email></user><meta>'.str_repeat(' ', 8192));

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage('Cannot read XML');

        UserData::lazyXml($this->file, 'users/user')->all();
    }

    public function test_it_dispatches_discriminated_classes(): void
    {
        $this->write(<<<'XML'
            <payments>
                <payment><type>card</type><amount>10</amount><last4>4242</last4></payment>
                <payment><type>bank</type><amount>20</amount><iban>UA00</iban></payment>
            </payments>
            XML);

        [$card, $bank] = PaymentMethodData::lazyXml($this->file, 'payments/payment')->all();

        $this->assertInstanceOf(CardPaymentData::class, $card);
        $this->assertSame(10, $card->amount);
        $this->assertSame('4242', $card->last4);
        $this->assertInstanceOf(BankPaymentData::class, $bank);
        $this->assertSame(20, $bank->amount);
        $this->assertSame('UA00', $bank->iban);
    }

    public function test_a_discriminator_can_be_read_from_an_attribute(): void
    {
        $this->write('<shapes><shape kind="circle"><radius>2.5</radius></shape></shapes>');

        $shape = XmlShapeData::lazyXml($this->file, 'shapes/shape')->first();

        $this->assertInstanceOf(XmlCircleData::class, $shape);
        $this->assertSame(2.5, $shape->radius);
    }

    public function test_a_stream_row_hydrates_the_same_class_as_a_plain_array(): void
    {
        $this->write(self::CATALOG);

        $row = XmlStream::read(XmlOfferData::class, $this->file, 'catalog/shop/offers/offer')->current();

        $this->assertTrue(XmlOfferData::from($row)->equals($this->offers()->first()));
        $this->assertSame('UAH', XmlOfferData::from($this->offers()->first()->toArray())->price->currency);
    }

    public function test_xml_source_attributes_cannot_be_combined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('#[XmlAttribute], #[XmlElement] and #[XmlText] cannot be combined');

        ConflictXmlSourceData::from(['value' => 'x']);
    }

    public function test_it_dispatches_to_the_discriminator_fallback(): void
    {
        $this->write(<<<'XML'
            <channels>
                <item><channel>email</channel><address>a@example.com</address></item>
                <item><channel>sms</channel><payload>+380</payload></item>
            </channels>
            XML);

        [$email, $generic] = ChannelData::lazyXml($this->file, 'channels/item')->all();

        $this->assertInstanceOf(EmailChannelData::class, $email);
        $this->assertSame('a@example.com', $email->address);
        $this->assertInstanceOf(GenericChannelData::class, $generic);
        $this->assertSame('+380', $generic->payload);
    }

    public function test_it_throws_when_the_file_cannot_be_opened(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Cannot read XML from '{$this->file}'");

        UserData::lazyXml($this->file, 'users/user')->all();
    }

    public function test_it_throws_on_malformed_xml_after_yielding_the_readable_items(): void
    {
        $this->write('<users><user><name>Alice</name><email>a@example.com</email></user><user><name>Bob');

        $names = [];

        try {
            foreach (UserData::lazyXml($this->file, 'users/user') as $user) {
                $names[] = $user->name;
            }

            $this->fail('Expected a DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertStringContainsString('on line 1', $e->getMessage());
        }

        $this->assertSame(['Alice'], $names);
    }

    /** @return LazyCollection<int, XmlOfferData> */
    private function offers(): LazyCollection
    {
        $this->write(self::CATALOG);

        return XmlOfferData::lazyXml($this->file, 'catalog/shop/offers/offer');
    }

    private function write(string $xml): void
    {
        file_put_contents($this->file, $xml);
    }
}
