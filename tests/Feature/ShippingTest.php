<?php

namespace Tests\Feature;

use App\Support\Shipping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    /**
     * The whole rate card in one table. Delivery is the number customers argue
     * about, so every cell is stated rather than inferred from two examples.
     *
     * @return array<string, array{int, string, string|null, int}>
     */
    public static function rates(): array
    {
        return [
            //                              net,   method,     city,          expected
            'Casa, petit panier' => [12000, 'standard', 'Casablanca', 0],
            'Casa, gros panier' => [90000, 'standard', 'Casablanca', 0],
            'Casa, express' => [12000, 'express', 'Casablanca', 0],
            'hors Casa, sous le seuil' => [12000, 'standard', 'Rabat', 2500],
            'hors Casa, juste sous' => [59900, 'standard', 'Rabat', 2500],
            'hors Casa, pile au seuil' => [60000, 'standard', 'Rabat', 0],
            'hors Casa, au-dessus' => [90000, 'standard', 'Agadir', 0],
            'hors Casa, express' => [12000, 'express', 'Tanger', 3500],
            'express reste payant' => [90000, 'express', 'Tanger', 3500],
            'panier vide' => [0, 'standard', 'Rabat', 0],
            'ville inconnue' => [12000, 'standard', null, 2500],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rates')]
    public function test_the_rate_card(int $net, string $method, ?string $city, int $expected): void
    {
        $this->assertSame($expected, Shipping::costFor($net, $method, $city));
    }

    /** The city arrives as free text from a form, not as an id. */
    public function test_casablanca_is_recognised_however_it_is_typed(): void
    {
        foreach (['Casablanca', 'casablanca', 'CASABLANCA', 'Casa', ' casablanca '] as $city) {
            $this->assertTrue(Shipping::isSameDayCity($city), "Refusé : {$city}");
        }

        foreach (['Rabat', 'Marrakech', 'Tanger', '', null] as $city) {
            $this->assertFalse(Shipping::isSameDayCity($city), "Accepté à tort : {$city}");
        }
    }

    public function test_casablanca_is_offered_one_same_day_option_and_no_express(): void
    {
        $options = Shipping::optionsFor('Casablanca');

        $this->assertCount(1, $options);
        $this->assertSame(0, $options[0]['cents']);
        $this->assertStringContainsString('jour même', $options[0]['delay']);
        $this->assertStringContainsString('20 h', $options[0]['delay']);
    }

    public function test_other_cities_are_offered_standard_and_express(): void
    {
        $options = Shipping::optionsFor('Rabat');

        $this->assertSame(['standard', 'express'], array_column($options, 'value'));
        $this->assertSame([2500, 3500], array_column($options, 'cents'));
        $this->assertSame('2 à 3 jours', $options[0]['delay']);
        $this->assertSame('24 h', $options[1]['delay']);
    }

    /** Above the threshold the standard option must show as free, not as 25 MAD. */
    public function test_the_standard_option_shows_free_above_the_threshold(): void
    {
        $this->assertSame(0, Shipping::optionsFor('Rabat', 60000)[0]['cents']);
    }

    /**
     * Google is told the same rates the checkout charges, so a shopper who
     * clicked through on "livraison gratuite" is not surprised at the total.
     */
    public function test_the_structured_data_matches_the_charged_rates(): void
    {
        $details = Shipping::structuredData();

        $this->assertCount(2, $details);

        $byRegion = collect($details)->keyBy(fn ($d) => $d['shippingDestination']['addressRegion']);

        $this->assertSame('0.00', $byRegion['Casablanca']['shippingRate']['value']);
        $this->assertSame('25.00', $byRegion['Maroc']['shippingRate']['value']);
        $this->assertSame('MAD', $byRegion['Maroc']['shippingRate']['currency']);
        $this->assertSame('MA', $byRegion['Maroc']['shippingDestination']['addressCountry']);
        $this->assertSame(2, $byRegion['Maroc']['deliveryTime']['transitTime']['minValue']);
        $this->assertSame(3, $byRegion['Maroc']['deliveryTime']['transitTime']['maxValue']);
        $this->assertSame(0, $byRegion['Casablanca']['deliveryTime']['transitTime']['maxValue']);
    }
}
