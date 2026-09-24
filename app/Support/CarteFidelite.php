<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Commande;
use App\Models\ReductionFidelite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\FontProcessor;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\Font;
use Intervention\Image\Typography\FontFactory;

/**
 * Carte de fidélité PNG (ancien formulaire libre — seul parcours où une
 * commande est finale dès le clic du client, voir CommandeController::store()).
 * Rendu depuis le gabarit resources/loyalty/template.png, coordonnées et
 * couleurs suivant resources/loyalty/SPECS.md à la lettre. Générée au plus
 * une fois par palier réellement débloqué (contrainte unique en base sur
 * client_id + numero_commande) ; stockée sur le disque local, jamais
 * exposée par son chemin — seul ReductionFidelite::token en donne l'accès
 * (CarteFideliteController), même principe que le reçu (recu_token).
 */
class CarteFidelite
{
    private const LARGEUR = 2480;

    private const HAUTEUR = 3508;

    private const X_NOM = 1258;

    private const Y_NOM_HAUT = 1303;

    private const LARGEUR_MAX_NOM = 1700;

    private const TAILLE_NOM_INITIALE = 100;

    private const TAILLE_NOM_MIN = 40;

    private const CENTRES_COCHES = [422, 985, 1526, 2045];

    private const Y_COCHES_HAUT = 2626;

    private const X_TEXTE_BAS = 1240;

    private const Y_TEXTE_BAS_HAUT = 2927;

    private const INTERLIGNE_TEXTE_BAS = 70;

    private const TAILLE_TEXTE_BAS = 61;

    private const COULEUR_TEXTE_BAS = '#8E3913';

    /**
     * Génère (si nécessaire) et retourne la réduction débloquée par cette
     * commande — null si son rang (numero_commande_client) ne tombe pas
     * sur un palier. Idempotente : rejouée sur un rang déjà débloqué, elle
     * retourne l'enregistrement existant sans régénérer d'image.
     */
    public static function genererSiPalierAtteint(Commande $commande, Client $client): ?ReductionFidelite
    {
        $numero = $commande->numero_commande_client;

        if (blank($numero)) {
            return null;
        }

        $pourcentage = Client::avantagePourNumero($numero);

        if ($pourcentage === null) {
            return null;
        }

        $existante = ReductionFidelite::query()
            ->where('client_id', $client->id)
            ->where('numero_commande', $numero)
            ->first();

        if ($existante) {
            return $existante;
        }

        $palier = (($numero - 1) % 8) + 1;

        return DB::transaction(function () use ($commande, $client, $numero, $palier, $pourcentage) {
            // Reverrouille sous transaction : deux requêtes concurrentes sur
            // le même rang ne doivent jamais produire deux images.
            $existante = ReductionFidelite::query()
                ->where('client_id', $client->id)
                ->where('numero_commande', $numero)
                ->lockForUpdate()
                ->first();

            if ($existante) {
                return $existante;
            }

            $chemin = self::dessiner($client, $palier, $pourcentage, $numero);

            return ReductionFidelite::create([
                'client_id' => $client->id,
                'commande_id' => $commande->id,
                'numero_commande' => $numero,
                'palier' => $palier,
                'pourcentage' => $pourcentage,
                'chemin_fichier' => $chemin,
                'token' => ReductionFidelite::genererToken(),
            ]);
        });
    }

    /**
     * Dessine la carte et la stocke sur le disque local. Retourne le
     * chemin relatif (Storage::disk('local')).
     */
    private static function dessiner(Client $client, int $palier, int $pourcentage, int $numero): string
    {
        $policeGras = resource_path('fonts/Poppins-Bold.ttf');
        $policeLegere = resource_path('fonts/Poppins-Light.ttf');

        $manager = ImageManager::gd();
        $image = $manager->read(resource_path('loyalty/template.png'));

        // Nom : majuscules + virgule, taille réduite jusqu'à tenir dans la
        // largeur cible.
        $nomAffiche = mb_strtoupper(trim($client->nom)).',';
        $taille = self::taillePourLargeur($nomAffiche, $policeGras, self::LARGEUR_MAX_NOM);

        $image->text($nomAffiche, self::X_NOM, self::Y_NOM_HAUT, function (FontFactory $font) use ($policeGras, $taille) {
            $font->filename($policeGras);
            $font->size($taille);
            $font->color('#000000');
            $font->align('center');
            $font->valign('top');
        });

        // Coches : $palier est le seuil littéral (2, 4, 6 ou 8, cf. SPECS.md
        // colonne « Commandes validées ») — le nombre de coches à afficher
        // est sa position dans le cycle (1 à 4), donc $palier / 2.
        $coche = $manager->read(resource_path('loyalty/check.png'));
        $largeurCoche = $coche->width();
        $nombreCoches = intdiv($palier, 2);

        foreach (array_slice(self::CENTRES_COCHES, 0, $nombreCoches) as $centreX) {
            $image->place($coche, 'top-left', (int) round($centreX - $largeurCoche / 2), self::Y_COCHES_HAUT);
        }

        // Texte du bas : retours à la ligne imposés, une ligne vide entre
        // les deux paragraphes — jamais de wrap automatique.
        $lignes = [
            "Vous venez de débloquer -{$pourcentage} % de réduction sur votre",
            'prochaine commande RÉVOLUTION.',
            '',
            'Pour bénéficier de votre réduction, il vous suffira de',
            'nous envoyer une capture de votre carte de fidélité au',
            'moment de votre prochaine commande.',
        ];

        $y = self::Y_TEXTE_BAS_HAUT;

        foreach ($lignes as $ligne) {
            if ($ligne !== '') {
                $image->text($ligne, self::X_TEXTE_BAS, $y, function (FontFactory $font) use ($policeLegere) {
                    $font->filename($policeLegere);
                    $font->size(self::TAILLE_TEXTE_BAS);
                    $font->color(self::COULEUR_TEXTE_BAS);
                    $font->align('center');
                    $font->valign('top');
                });
            }

            $y += self::INTERLIGNE_TEXTE_BAS;
        }

        $chemin = "loyalty/{$client->id}-{$numero}.png";
        Storage::disk('local')->put($chemin, (string) $image->toPng());

        return $chemin;
    }

    /**
     * Réduit la taille de police (par pas de 2 px) jusqu'à ce que le texte
     * tienne dans la largeur cible — jamais en dessous de TAILLE_NOM_MIN.
     */
    private static function taillePourLargeur(string $texte, string $police, int $largeurMax): int
    {
        $processeur = new FontProcessor();
        $police = new Font($police);

        for ($taille = self::TAILLE_NOM_INITIALE; $taille > self::TAILLE_NOM_MIN; $taille -= 2) {
            $police->setSize($taille);

            if ($processeur->boxSize($texte, $police)->width() <= $largeurMax) {
                return $taille;
            }
        }

        return self::TAILLE_NOM_MIN;
    }
}
