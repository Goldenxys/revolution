<?php

namespace App\Support;

use Intervention\Image\Drivers\Gd\FontProcessor;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\Font;
use Intervention\Image\Typography\FontFactory;

/**
 * Rendu de la carte de fidélité RÉVOLUTION à partir du template graphique
 * (resources/loyalty/template.png) — applique resources/loyalty/SPECS.md à
 * la lettre. Pur rendu : ne compte rien, ne persiste rien, ne décide pas si
 * un palier est atteint — ces décisions restent dans le parcours appelant.
 */
class LoyaltyCardService
{
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

    private const TAILLE_TEXTE_BAS_MIN = 30;

    private const LARGEUR_MAX_TEXTE_BAS = 1700;

    private const COULEUR_TEXTE_BAS = '#8E3913';

    /**
     * Génère le PNG de la carte pour un palier atteint (2, 4, 6 ou 8
     * commandes) et le pourcentage de réduction débloqué.
     *
     * @param  int  $palier  Seuil atteint : 2, 4, 6 ou 8.
     * @param  int  $pourcentage  Réduction débloquée à ce palier.
     * @return string Contenu binaire du PNG généré.
     */
    public static function generer(string $nom, int $palier, int $pourcentage): string
    {
        $manager = ImageManager::gd();
        $image = $manager->read(resource_path('loyalty/template.png'));

        self::dessinerNom($image, $nom);
        self::dessinerCoches($manager, $image, $palier);
        self::dessinerTexteBas($image, $pourcentage);

        return (string) $image->toPng();
    }

    /**
     * Génère le PNG de la carte pour un palier pas encore atteint : mêmes
     * nom et coches (0 à 3, selon le palier courant), mais un texte du bas
     * qui annonce la progression plutôt qu'un avantage débloqué.
     *
     * @param  int  $palier  Palier courant du client (peut être impair).
     * @param  int  $commandesRestantes  Commandes restantes avant le
     *                                   prochain palier pair — reprendre
     *                                   Client::commandesRestantesPourPalier().
     * @param  int  $prochainAvantage  Réduction de ce prochain palier —
     *                                 reprendre Client::prochainAvantagePourPalier().
     * @return string Contenu binaire du PNG généré.
     */
    public static function genererProgression(string $nom, int $palier, int $commandesRestantes, int $prochainAvantage): string
    {
        $manager = ImageManager::gd();
        $image = $manager->read(resource_path('loyalty/template.png'));

        self::dessinerNom($image, $nom);
        self::dessinerCoches($manager, $image, $palier);
        self::dessinerTexteBasProgression($image, $commandesRestantes, $prochainAvantage);

        return (string) $image->toPng();
    }

    private static function dessinerNom($image, string $nom): void
    {
        $nomAffiche = mb_strtoupper(trim($nom)).',';
        $police = resource_path('fonts/Poppins-Bold.ttf');
        $taille = self::taillePourLargeur($nomAffiche, $police, self::LARGEUR_MAX_NOM);

        $image->text($nomAffiche, self::X_NOM, self::Y_NOM_HAUT, function (FontFactory $font) use ($police, $taille) {
            $font->filename($police);
            $font->size($taille);
            $font->color('#000000');
            $font->align('center');
            $font->valign('top');
        });
    }

    private static function dessinerCoches(ImageManager $manager, $image, int $palier): void
    {
        $coche = $manager->read(resource_path('loyalty/check.png'));
        $largeurCoche = $coche->width();
        $nombreCoches = intdiv($palier, 2);

        foreach (array_slice(self::CENTRES_COCHES, 0, $nombreCoches) as $centreX) {
            $image->place($coche, 'top-left', (int) round($centreX - $largeurCoche / 2), self::Y_COCHES_HAUT);
        }
    }

    private static function dessinerTexteBas($image, int $pourcentage): void
    {
        $police = resource_path('fonts/Poppins-Light.ttf');

        $lignes = [
            "Vous venez de débloquer -{$pourcentage} % de réduction sur votre",
            'prochaine commande RÉVOLUTION.',
            '',
            'Pour bénéficier de votre réduction, il vous suffira de',
            'nous envoyer une capture de votre carte de fidélité au',
            'moment de votre prochaine commande.',
        ];

        foreach ($lignes as $i => $ligne) {
            $y = self::Y_TEXTE_BAS_HAUT + $i * self::INTERLIGNE_TEXTE_BAS;

            if ($ligne === '') {
                continue;
            }

            $image->text($ligne, self::X_TEXTE_BAS, $y, function (FontFactory $font) use ($police) {
                $font->filename($police);
                $font->size(self::TAILLE_TEXTE_BAS);
                $font->color(self::COULEUR_TEXTE_BAS);
                $font->align('center');
                $font->valign('top');
            });
        }
    }

    /**
     * Texte du bas pour un palier pas encore atteint (§ progression) :
     * mêmes police, couleur, position et interligne que le texte « palier
     * débloqué », mais un contenu variable (N commandes restantes, Y %) dont
     * la largeur n'est pas garantie à l'avance — on réduit donc la taille du
     * bloc si besoin, comme pour le nom.
     */
    private static function dessinerTexteBasProgression($image, int $commandesRestantes, int $prochainAvantage): void
    {
        $police = resource_path('fonts/Poppins-Light.ttf');
        $texteCommande = $commandesRestantes > 1 ? 'commandes' : 'commande';

        $lignes = [
            "Plus que {$commandesRestantes} {$texteCommande} pour débloquer -{$prochainAvantage} % de réduction",
            'sur votre prochaine commande RÉVOLUTION.',
            '',
            'Votre carte se complète à chaque commande.',
        ];

        $taille = self::taillePourLargeurMax($lignes, $police, self::LARGEUR_MAX_TEXTE_BAS, self::TAILLE_TEXTE_BAS, self::TAILLE_TEXTE_BAS_MIN);

        foreach ($lignes as $i => $ligne) {
            $y = self::Y_TEXTE_BAS_HAUT + $i * self::INTERLIGNE_TEXTE_BAS;

            if ($ligne === '') {
                continue;
            }

            $image->text($ligne, self::X_TEXTE_BAS, $y, function (FontFactory $font) use ($police, $taille) {
                $font->filename($police);
                $font->size($taille);
                $font->color(self::COULEUR_TEXTE_BAS);
                $font->align('center');
                $font->valign('top');
            });
        }
    }

    private static function taillePourLargeur(string $texte, string $police, int $largeurMax): int
    {
        return self::taillePourLargeurMax([$texte], $police, $largeurMax, self::TAILLE_NOM_INITIALE, self::TAILLE_NOM_MIN);
    }

    /**
     * Plus grande taille de police (en partant de $tailleInitiale, par pas
     * de 2) pour laquelle chaque ligne non vide de $lignes tient dans
     * $largeurMax — sert au nom (une seule ligne) et au bloc de progression
     * (plusieurs lignes, doivent toutes tenir à la même taille).
     *
     * @param  string[]  $lignes
     */
    private static function taillePourLargeurMax(array $lignes, string $police, int $largeurMax, int $tailleInitiale, int $tailleMin): int
    {
        $processeur = new FontProcessor;
        $font = new Font($police);

        for ($taille = $tailleInitiale; $taille > $tailleMin; $taille -= 2) {
            $font->setSize($taille);

            $tientDansLaLargeur = true;
            foreach ($lignes as $ligne) {
                if ($ligne === '') {
                    continue;
                }

                if ($processeur->boxSize($ligne, $font)->width() > $largeurMax) {
                    $tientDansLaLargeur = false;
                    break;
                }
            }

            if ($tientDansLaLargeur) {
                return $taille;
            }
        }

        return $tailleMin;
    }
}
