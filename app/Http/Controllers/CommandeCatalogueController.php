<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResoutClientEtNotifie;
use App\Http\Requests\StoreCommandeCatalogueRequest;
use App\Models\Article;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Couleur;
use App\Models\Taille;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommandeCatalogueController extends Controller
{
    use ResoutClientEtNotifie;

    /**
     * `?collection=<slug>` pré-sélectionne une collection (utilisé par les
     * boutons de l'accueil) sans présumer qu'elle existe : le JS gère
     * simplement l'absence de correspondance en repartant du premier écran.
     *
     * Le catalogue est injecté côté serveur (CatalogueController::payload())
     * pour un premier affichage sans flash de contenu vide ; le même JSON
     * reste disponible en GET /commande/catalogue.json si le JS a besoin de
     * le rafraîchir.
     */
    public function creer(CatalogueController $catalogue): View
    {
        return view('commande.catalogue', [
            'catalogue' => $catalogue->payload(),
            'collectionPreselectionnee' => request()->query('collection'),
        ]);
    }

    public function store(StoreCommandeCatalogueRequest $request): RedirectResponse
    {
        $donnees = $request->validated();

        $commande = DB::transaction(function () use ($donnees) {
            $article = Article::with('typeArticle')->findOrFail($donnees['article_id']);
            $taille = ($article->gere_tailles && $donnees['taille_id']) ? Taille::find($donnees['taille_id']) : null;
            $couleur = ($article->gere_couleurs && $donnees['couleur_id']) ? Couleur::find($donnees['couleur_id']) : null;

            $client = $this->resoudreClient($donnees);

            // Deux règles non négociables : le prix vient toujours du
            // catalogue en base (jamais d'un champ soumis par le
            // formulaire), et les frais de livraison sont toujours
            // recalculés depuis la configuration — exactement comme pour
            // l'ancien formulaire.
            $fraisLivraison = config('revolution.communes')[$donnees['commune']];
            $prixUnitaire = $article->prix;
            $sousTotal = $prixUnitaire * $donnees['quantite'];
            $numeroCommandeClient = $client->nb_commandes;
            $remisePourcentage = Client::avantagePourNumero($numeroCommandeClient) ?? 0;
            $remiseMontant = (int) round(($sousTotal + $fraisLivraison) * $remisePourcentage / 100);
            $total = $sousTotal + $fraisLivraison - $remiseMontant;

            $commande = Commande::create([
                'client_id' => $client->id,
                'commune' => $donnees['commune'],
                'frais_livraison' => $fraisLivraison,
                'quartier' => $donnees['quartier'] ?? null,
                'mode_livraison' => $donnees['mode_livraison'],
                'date_souhaitee' => $donnees['date_souhaitee'] ?? null,
                'heure_souhaitee' => $donnees['heure_souhaitee'] ?? null,
                'numero_commande_client' => $numeroCommandeClient,
                'sous_total' => $sousTotal,
                'remise_pourcentage' => $remisePourcentage,
                'remise_montant' => $remiseMontant,
                'total' => $total,
                'statut' => 'nouvelle',
                'utilise_catalogue' => true,
            ]);

            $commande->lignes()->create([
                'article_id' => $article->id,
                'article_nom' => $article->nom,
                'taille_libelle' => $taille?->libelle,
                'couleur_nom' => $couleur?->nom,
                'quantite' => $donnees['quantite'],
                'prix_unitaire' => $prixUnitaire,
                'verset' => $donnees['verset'] ?? null,
                'modele' => $donnees['modele'] ?? null,
            ]);

            return $commande;
        });

        $this->envoyerMailCommande($commande);
        $this->notifierNouvelleCommande($commande);

        return redirect()->route('commande.confirmation', $commande->reference);
    }
}
