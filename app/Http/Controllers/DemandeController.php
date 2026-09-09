<?php

namespace App\Http\Controllers;

use App\Filament\Resources\DemandeResource\Pages\ComposerDemande;
use App\Http\Controllers\Concerns\ResoutClientEtNotifie;
use App\Http\Requests\StoreDemandeRequest;
use App\Mail\DemandeDeposee;
use App\Mail\DemandeRecue;
use App\Models\Article;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\Parametre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Parcours de demande V2 (§4) : « Le client ne passe plus une commande. Il
 * dépose une demande. La commande naît quand la gérante la valide. »
 *
 * Le formulaire est court, textuel, sans aucune image d'article. Il
 * n'affiche aucune disponibilité et n'enregistre aucun total ferme : tous
 * les montants de la commande créée sont à zéro tant que la gérante n'a
 * pas validé.
 */
class DemandeController extends Controller
{
    use ResoutClientEtNotifie;

    public function creer(): View
    {
        return view('commande.demande', [
            'articles' => $this->optionsArticles(),
            'communes' => config('revolution.communes'),
        ]);
    }

    public function store(StoreDemandeRequest $request): RedirectResponse
    {
        $donnees = $request->validated();

        $commande = DB::transaction(function () use ($donnees) {
            $client = $this->resoudreProspect($donnees);

            // Frais recalculés côté serveur, jamais depuis le formulaire.
            $fraisLivraison = config('revolution.communes')[$donnees['commune']];

            $commande = Commande::create([
                'client_id' => $client->id,
                'commune' => $donnees['commune'],
                'frais_livraison' => $fraisLivraison,
                'quartier' => $donnees['quartier'] ?? null,
                'mode_livraison' => $donnees['mode_livraison'],
                'date_souhaitee' => $donnees['date_souhaitee'] ?? null,
                'heure_souhaitee' => $donnees['heure_souhaitee'] ?? null,
                'statut' => 'en_attente',
                'souhaits_client' => $this->normaliserSouhaits($donnees['souhaits']),
                'message_client' => $donnees['precisions'] ?? null,
                // Aucune vente à ce stade : tous les totaux restent à zéro.
                'sous_total' => 0,
                'remise_pourcentage' => 0,
                'remise_montant' => 0,
                'total' => 0,
                'total_articles' => 0,
                'total_a_payer' => 0,
            ]);

            CommandeJournal::consigner($commande, 'creee', [
                'canal' => 'formulaire_demande_v2',
                'nb_souhaits' => count($donnees['souhaits']),
            ]);

            return $commande;
        });

        $this->notifierGerante($commande);
        $this->accuserReceptionCliente($commande);

        return redirect()->route('commande.demande.merci', $commande->reference);
    }

    public function confirmation(string $reference): View
    {
        $commande = Commande::with('client')
            ->where('reference', $reference)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        return view('commande.demande-confirmation', [
            'commande' => $commande,
            'client' => $commande->client,
        ]);
    }

    /**
     * Alerte la gérante : cloche Filament (vue en direct) + e-mail, avec un
     * lien qui pointe droit vers le compositeur de cette demande.
     */
    private function notifierGerante(Commande $commande): void
    {
        $urlCompositeur = ComposerDemande::getUrl(['record' => $commande]);

        $this->notifierNouvelleCommande($commande, $urlCompositeur);

        try {
            Mail::to(Parametre::emailReception())->queue(new DemandeDeposee($commande, $urlCompositeur));
        } catch (\Throwable $e) {
            Log::error('Échec de mise en file du mail « demande à valider »', [
                'commande' => $commande->reference,
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    /**
     * « Votre demande est bien reçue » — seulement si la cliente a laissé
     * un e-mail. Aucun montant ferme (§7.1, ligne 1).
     */
    private function accuserReceptionCliente(Commande $commande): void
    {
        if (blank($commande->client?->email)) {
            return;
        }

        try {
            Mail::to($commande->client->email)->queue(new DemandeRecue($commande));
            CommandeJournal::consigner($commande, 'email_envoye', ['destinataire' => 'cliente', 'type' => 'accuse_demande']);
        } catch (\Throwable $e) {
            Log::error('Accusé de réception cliente non envoyé', [
                'commande' => $commande->reference,
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fige le nom d'article (résolu depuis le catalogue) à côté de l'id, et
     * nettoie les valeurs — c'est la trace de la demande, jamais modifiée
     * après soumission.
     *
     * @param  array<int, array<string, mixed>>  $souhaits
     * @return array<int, array<string, mixed>>
     */
    private function normaliserSouhaits(array $souhaits): array
    {
        $noms = Article::query()->whereIn('id', collect($souhaits)->pluck('article_id'))->pluck('nom', 'id');

        return collect($souhaits)->map(fn (array $s) => [
            'article_id' => (int) $s['article_id'],
            'article_nom' => $noms[$s['article_id']] ?? 'Article',
            'taille' => $s['taille'] ?? null,
            'couleur' => $s['couleur'] ?? null,
            'quantite' => (int) $s['quantite'],
            'note' => null,
        ])->values()->all();
    }

    /**
     * Liste textuelle des articles actifs, groupés par collection — nom et
     * prix, sans photo, sans filtre de disponibilité (§4.2 : « la gérante
     * tranchera »).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function optionsArticles(): Collection
    {
        return Article::query()
            ->where('active', true)
            ->with(['collection:id,nom,ordre', 'typeArticle:id,gere_tailles,gere_couleurs'])
            ->orderBy('ordre')
            ->get(['id', 'collection_id', 'type_article_id', 'nom', 'prix'])
            ->map(fn (Article $a) => [
                'id' => $a->id,
                'nom' => $a->nom,
                'prix' => $a->prix,
                'collection' => $a->collection?->nom ?? 'RÉVOLUTION',
                'collection_ordre' => $a->collection?->ordre ?? 999,
                'gere_tailles' => $a->gere_tailles,
                'gere_couleurs' => $a->gere_couleurs,
            ])
            ->sortBy([['collection_ordre', 'asc'], ['nom', 'asc']])
            ->values();
    }
}
