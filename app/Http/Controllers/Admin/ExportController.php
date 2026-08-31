<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Commande;
use App\Support\Francais;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export CSV de la journée : mêmes colonnes que le tableau des
     * commandes du jour de l'Espace RÉVOLUTION.
     */
    public function commandesJour(Request $request): StreamedResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $date = $date->greaterThan(Carbon::today()) ? Carbon::today() : $date;

        $commandes = Commande::with('client')
            ->whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get();

        $entetes = [
            'Heure', 'Client', 'Téléphone', 'Collection', 'Article',
            'Taille', 'Couleur', 'Commune', 'Frais (F CFA)', 'Quartier',
            'Mode de livraison', 'Rang de fidélité',
        ];

        $lignes = $commandes->map(function (Commande $commande) {
            $article = $commande->estMyVerse()
                ? trim(($commande->verset_reference ?: 'Verset').' · '.Str::limit($commande->verset_texte ?: '', 60))
                : trim(($commande->type_article ?? '').' '.($commande->nom_article ?? ''));

            $livraison = $commande->estYango()
                ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
                : 'Livreur normal — selon les zones';

            $rang = $commande->numero_commande_client <= 1
                ? 'Nouveau'
                : Francais::ordinal($commande->numero_commande_client).' cde';

            return [
                $commande->created_at->format('H:i'),
                $commande->client->nom,
                $commande->client->telephone,
                $commande->estMyVerse() ? 'MY VERSE' : 'Autre collection',
                $article,
                $commande->taille,
                $commande->couleur ?? '',
                $commande->commune,
                $commande->frais_livraison,
                $commande->quartier ?? '',
                $livraison,
                $rang,
            ];
        })->all();

        return $this->reponseCsv(
            "commandes-revolution-{$date->format('Y-m-d')}.csv",
            $entetes,
            $lignes
        );
    }

    /**
     * Export CSV du fichier clients complet.
     */
    public function clients(): StreamedResponse
    {
        $clients = Client::query()->orderByDesc('derniere_commande_at')->get();

        $entetes = [
            'Nom', 'Téléphone', 'Email', 'Commune', 'Nombre de commandes',
            'Palier', 'Avantage en cours', 'Première commande', 'Dernière commande',
        ];

        $lignes = $clients->map(fn (Client $client) => [
            $client->nom,
            $client->telephone,
            $client->email ?? '',
            $client->commune ?? '',
            $client->nb_commandes,
            "{$client->palier}/8",
            $client->avantage ? "-{$client->avantage} %" : '',
            $client->premiere_commande_at?->format('d/m/Y') ?? '',
            $client->derniere_commande_at?->format('d/m/Y') ?? '',
        ])->all();

        return $this->reponseCsv('clients-revolution.csv', $entetes, $lignes);
    }

    /**
     * @param  array<int, string>  $entetes
     * @param  array<int, array<int, mixed>>  $lignes
     */
    private function reponseCsv(string $nomFichier, array $entetes, array $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes) {
            $flux = fopen('php://output', 'w');

            // BOM UTF-8 pour qu'Excel affiche correctement les accents.
            fwrite($flux, "\xEF\xBB\xBF");

            fputcsv($flux, $entetes, ';');

            foreach ($lignes as $ligne) {
                fputcsv($flux, $ligne, ';');
            }

            fclose($flux);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
