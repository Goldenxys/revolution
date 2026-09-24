<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResoutClientEtNotifie;
use App\Http\Requests\StoreCommandeRequest;
use App\Models\Commande;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommandeController extends Controller
{
    use ResoutClientEtNotifie;

    public function myVerse(): View
    {
        return view('commande.formulaire', ['variante' => 'my_verse']);
    }

    public function autre(): View
    {
        return view('commande.formulaire', ['variante' => 'autre']);
    }

    public function store(StoreCommandeRequest $request): RedirectResponse
    {
        $donnees = $request->validated();

        $commande = DB::transaction(function () use ($donnees) {
            $client = $this->resoudreClient($donnees);

            // Frais de livraison toujours recalculés côté serveur depuis la
            // configuration : on ignore la valeur envoyée par le formulaire.
            $fraisLivraison = config('revolution.communes')[$donnees['commune']];

            return Commande::create([
                'client_id' => $client->id,
                'collection' => $donnees['collection'],
                'type_article' => $donnees['type_article'] ?? null,
                'nom_article' => $donnees['nom_article'] ?? null,
                'taille' => $donnees['taille'] ?? null,
                'couleur' => $donnees['couleur'] ?? null,
                'verset_reference' => $donnees['verset_reference'] ?? null,
                'verset_texte' => $donnees['verset_texte'] ?? null,
                'commune' => $donnees['commune'],
                'frais_livraison' => $fraisLivraison,
                'quartier' => $donnees['quartier'] ?? null,
                'mode_livraison' => $donnees['mode_livraison'],
                'date_souhaitee' => $donnees['date_souhaitee'] ?? null,
                'heure_souhaitee' => $donnees['heure_souhaitee'] ?? null,
                'numero_commande_client' => $client->nb_commandes,
            ]);
        });

        $this->envoyerMailCommande($commande);
        $this->notifierNouvelleCommande($commande);

        return redirect()->route('commande.confirmation', $commande->reference);
    }

    public function show(string $reference): View
    {
        $commande = Commande::with('client')->where('reference', $reference)->firstOrFail();

        return view('commande.confirmation', [
            'commande' => $commande,
            'client' => $commande->client,
        ]);
    }
}
