<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Support\LoyaltyCardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Vérification visuelle de LoyaltyCardService : génère les 8 paliers du
 * cycle de fidélité pour un nom normal et un nom très long (déclenche le
 * rétrécissement de police), dans storage/app/public/loyalty/test/.
 *
 * resources/loyalty/reference_8_commandes.png est une maquette de calage
 * des positions (elle affiche −15 % avec 4 coches) — elle ne sert pas à
 * vérifier le pourcentage affiché, qui dépend de config('revolution.paliers').
 */
class GenererCartesFideliteDeTest extends Command
{
    protected $signature = 'loyalty:test-card';

    protected $description = 'Génère les 8 paliers de la carte de fidélité pour deux noms de test, en PNG';

    public function handle(): int
    {
        $dossier = 'loyalty/test';
        Storage::disk('public')->deleteDirectory($dossier);
        Storage::disk('public')->makeDirectory($dossier);

        $noms = [
            'DJIEHI CARINE',
            'Un Nom Vraiment Tres Tres Long Qui Devrait Faire Retrecir La Police',
        ];

        foreach ($noms as $nom) {
            $sousDossier = "{$dossier}/".Str::slug($nom);
            Storage::disk('public')->makeDirectory($sousDossier);

            for ($palier = 1; $palier <= 8; $palier++) {
                $pourcentage = Client::avantagePourNumero($palier);

                $png = $pourcentage !== null
                    ? LoyaltyCardService::generer($nom, $palier, $pourcentage)
                    : LoyaltyCardService::genererProgression(
                        $nom,
                        $palier,
                        Client::commandesRestantesPourPalier($palier),
                        Client::prochainAvantagePourPalier($palier)
                    );

                $chemin = "{$sousDossier}/palier-{$palier}.png";
                Storage::disk('public')->put($chemin, $png);

                $this->line("  {$chemin}");
            }
        }

        $this->info('Cartes générées dans '.Storage::disk('public')->path($dossier));

        return self::SUCCESS;
    }
}
