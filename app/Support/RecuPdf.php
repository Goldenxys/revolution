<?php

namespace App\Support;

use App\Models\Commande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Reçu PDF d'une commande validée (§7.2) — une page : logo, référence,
 * date, coordonnées, lignes, remise, chiffre d'affaires et frais de
 * livraison sur DEUX lignes distinctes, total à payer, modalités de
 * livraison. Stocké dans storage/app/recus/{reference}.pdf.
 */
class RecuPdf
{
    private const DOSSIER = 'recus';

    public static function cheminRelatif(Commande $commande): string
    {
        return self::DOSSIER.'/'.$commande->reference.'.pdf';
    }

    public static function cheminAbsolu(Commande $commande): string
    {
        return Storage::disk('local')->path(self::cheminRelatif($commande));
    }

    public static function existe(Commande $commande): bool
    {
        return Storage::disk('local')->exists(self::cheminRelatif($commande));
    }

    /**
     * (Re)génère le PDF et le stocke. Idempotent : rappelé au besoin
     * (« Renvoyer le reçu »), il réécrit simplement le fichier.
     */
    public static function generer(Commande $commande): string
    {
        $commande->loadMissing(['client', 'lignes']);

        $pdf = Pdf::loadView('pdf.recu', ['commande' => $commande, 'client' => $commande->client])
            ->setPaper('a4');

        Storage::disk('local')->put(self::cheminRelatif($commande), $pdf->output());

        return self::cheminRelatif($commande);
    }

    /**
     * Contenu binaire du PDF, en le générant d'abord s'il manque.
     */
    public static function contenu(Commande $commande): string
    {
        if (! self::existe($commande)) {
            self::generer($commande);
        }

        return Storage::disk('local')->get(self::cheminRelatif($commande));
    }
}
