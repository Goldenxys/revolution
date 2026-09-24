<?php

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Support\LoyaltyCardService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class LoyaltyCardServiceTest extends TestCase
{
    private const CENTRES_COCHES = [422, 985, 1526, 2045];

    /**
     * Un pixel du centre de check.png (107,100) est opaque noir ; le même
     * point du template, quand aucune coche n'y est posée, est blanc pur
     * (vérifié directement sur les deux PNG sources). Sert à compter les
     * coches réellement dessinées sans dépendre d'une librairie d'OCR.
     */
    private function cochesDessinees(string $png): int
    {
        $image = imagecreatefromstring($png);

        $nombre = 0;
        foreach (self::CENTRES_COCHES as $centreX) {
            $rgba = imagecolorat($image, $centreX, 2626 + 100);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            if ($r < 50 && $g < 50 && $b < 50) {
                $nombre++;
            }
        }

        return $nombre;
    }

    public static function paliersEtCochesAttendues(): array
    {
        return [
            'palier 1' => [1, 0],
            'palier 2' => [2, 1],
            'palier 3' => [3, 1],
            'palier 4' => [4, 2],
            'palier 5' => [5, 2],
            'palier 6' => [6, 3],
            'palier 7' => [7, 3],
            'palier 8' => [8, 4],
        ];
    }

    #[DataProvider('paliersEtCochesAttendues')]
    public function test_le_nombre_de_coches_suit_intdiv_palier_2(int $palier, int $cochesAttendues): void
    {
        $pourcentage = Client::avantagePourNumero($palier);

        $png = $pourcentage !== null
            ? LoyaltyCardService::generer('DJIEHI CARINE', $palier, $pourcentage)
            : LoyaltyCardService::genererProgression(
                'DJIEHI CARINE',
                $palier,
                Client::commandesRestantesPourPalier($palier),
                Client::prochainAvantagePourPalier($palier)
            );

        $this->assertSame($cochesAttendues, $this->cochesDessinees($png));
    }

    public static function paliersImpairsEtValeursAttendues(): array
    {
        return [
            'palier 1 → 1 commande, -15 %' => [1, 1, 15],
            'palier 3 → 1 commande, -30 %' => [3, 1, 30],
            'palier 7 → 1 commande, -65 %' => [7, 1, 65],
        ];
    }

    #[DataProvider('paliersImpairsEtValeursAttendues')]
    public function test_commandes_restantes_et_prochain_avantage_sont_corrects(int $palier, int $restantesAttendues, int $avantageAttendu): void
    {
        $this->assertSame($restantesAttendues, Client::commandesRestantesPourPalier($palier));
        $this->assertSame($avantageAttendu, Client::prochainAvantagePourPalier($palier));
    }

    public function test_la_variante_debloquee_et_la_variante_progression_produisent_un_rendu_different(): void
    {
        $debloquee = LoyaltyCardService::generer('DJIEHI CARINE', 2, 15);
        $progression = LoyaltyCardService::genererProgression('DJIEHI CARINE', 1, 1, 15);

        $this->assertNotSame($debloquee, $progression);
    }

    public function test_les_deux_variantes_produisent_un_png_aux_dimensions_du_gabarit(): void
    {
        foreach ([
            LoyaltyCardService::generer('DJIEHI CARINE', 8, 65),
            LoyaltyCardService::genererProgression('DJIEHI CARINE', 7, 1, 65),
        ] as $png) {
            $image = imagecreatefromstring($png);
            $this->assertNotFalse($image);
            $this->assertSame(2480, imagesx($image));
            $this->assertSame(3508, imagesy($image));
        }
    }

    public function test_le_nom_tres_long_reduit_la_taille_de_police(): void
    {
        $methode = new ReflectionMethod(LoyaltyCardService::class, 'taillePourLargeur');
        $methode->setAccessible(true);

        $police = resource_path('fonts/Poppins-Bold.ttf');
        $tailleCourt = $methode->invoke(null, 'DJIEHI CARINE,', $police, 1700);
        $tailleLong = $methode->invoke(null, 'UN NOM VRAIMENT TRES TRES LONG QUI DEVRAIT FAIRE RETRECIR LA POLICE,', $police, 1700);

        $this->assertLessThan($tailleCourt, $tailleLong);
    }

    public function test_le_nom_tres_long_ne_fait_pas_echouer_le_rendu(): void
    {
        $png = LoyaltyCardService::generer(
            'Un Nom Vraiment Tres Tres Long Qui Devrait Faire Retrecir La Police',
            4,
            30
        );

        $image = imagecreatefromstring($png);
        $this->assertNotFalse($image);
        $this->assertSame(2480, imagesx($image));
        $this->assertSame(3508, imagesy($image));
    }
}
