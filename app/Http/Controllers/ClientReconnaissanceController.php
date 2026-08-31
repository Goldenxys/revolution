<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientReconnaissanceController extends Controller
{
    /**
     * Reconnaissance en direct pendant la saisie du formulaire : dès que la
     * cliente quitte le champ téléphone ou nom, on cherche un client déjà
     * connu (clé = 8 derniers chiffres du téléphone, repli sur le nom
     * normalisé) pour afficher le message de bienvenue et pré-remplir
     * nom/email.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $telephone = (string) $request->query('telephone', '');
        $nom = (string) $request->query('nom', '');

        $client = null;

        $cle = Client::cleDepuisTelephone($telephone);
        if (strlen($cle) === 8) {
            $client = Client::query()->where('cle', $cle)->first();
        }

        if (! $client && trim($nom) !== '') {
            $nomNormalise = Client::nomNormalise($nom);

            $client = Client::query()
                ->get(['id', 'nom'])
                ->first(fn (Client $candidat) => Client::nomNormalise($candidat->nom) === $nomNormalise);

            if ($client) {
                $client = Client::query()->find($client->id);
            }
        }

        if (! $client) {
            return response()->json(['connu' => false]);
        }

        $prochainNumero = $client->nb_commandes + 1;

        return response()->json([
            'connu' => true,
            'nom' => $client->nom,
            'email' => $client->email,
            'nb_commandes' => $client->nb_commandes,
            'prochaine_commande_numero' => $prochainNumero,
            // Avantage débloqué SI la cliente valide cette prochaine commande
            // (null si ce rang ne tombe pas sur un palier pair).
            'avantage_debloque' => Client::avantagePourNumero($prochainNumero),
        ]);
    }
}
