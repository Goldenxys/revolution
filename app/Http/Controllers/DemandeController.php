<?php

namespace App\Http\Controllers;

use App\Filament\Resources\DemandeResource\Pages\ComposerDemande;
use App\Http\Controllers\Concerns\ResoutClientEtNotifie;
use App\Http\Requests\StoreDemandeRequest;
use App\Mail\DemandeDeposee;
use App\Mail\DemandeRecue;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\Parametre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Parcours de demande V2 (§4) : « Le client ne passe plus une commande. Il
 * dépose une demande. La commande naît quand la gérante la valide. »
 *
 * Deux entrées distinctes, choisies sur l'accueil :
 *   • My Verse — la cliente fournit un ou plusieurs versets (référence +
 *     texte). Elle ne choisit ni taille ni couleur : la gérante les règle
 *     à la validation.
 *   • Autre collection — la cliente laisse seulement ses coordonnées et sa
 *     livraison. La gérante reprend l'article convenu sur WhatsApp.
 *
 * Aucun total ferme n'est enregistré : tous les montants restent à zéro
 * tant que la gérante n'a pas validé.
 */
class DemandeController extends Controller
{
    use ResoutClientEtNotifie;

    public function creerMyVerse(): View
    {
        return view('commande.demande', [
            'type' => 'my_verse',
            'communes' => config('revolution.communes'),
        ]);
    }

    public function creerAutre(): View
    {
        return view('commande.demande', [
            'type' => 'autre',
            'communes' => config('revolution.communes'),
        ]);
    }

    public function store(StoreDemandeRequest $request): RedirectResponse
    {
        $donnees = $request->validated();
        $estMyVerse = $donnees['collection'] === 'my_verse';

        $versets = $estMyVerse
            ? collect($donnees['versets'] ?? [])
                ->map(fn (array $v) => [
                    'reference' => filled($v['reference'] ?? null) ? trim($v['reference']) : null,
                    'texte' => filled($v['texte'] ?? null) ? trim($v['texte']) : null,
                ])
                ->values()
                ->all()
            : [];

        $commande = DB::transaction(function () use ($donnees, $estMyVerse, $versets) {
            $client = $this->resoudreProspect($donnees);

            // Frais recalculés côté serveur, jamais depuis le formulaire.
            $fraisLivraison = config('revolution.communes')[$donnees['commune']];

            $commande = Commande::create([
                'client_id' => $client->id,
                'collection' => $donnees['collection'],
                // Le premier verset alimente aussi les colonnes historiques
                // (affichage, mails). La liste complète — trace de la demande,
                // jamais modifiée — vit dans souhaits_client.
                'verset_reference' => $versets[0]['reference'] ?? null,
                'verset_texte' => $versets[0]['texte'] ?? null,
                // Taille et couleur : décidées par la gérante à la validation.
                'taille' => null,
                'couleur' => null,
                'souhaits_client' => $estMyVerse
                    ? ['collection' => 'my_verse', 'versets' => $versets]
                    : null,
                'commune' => $donnees['commune'],
                'frais_livraison' => $fraisLivraison,
                'quartier' => $donnees['quartier'] ?? null,
                'mode_livraison' => $donnees['mode_livraison'],
                'date_souhaitee' => $donnees['date_souhaitee'] ?? null,
                'heure_souhaitee' => $donnees['heure_souhaitee'] ?? null,
                'statut' => 'en_attente',
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
                'collection' => $donnees['collection'],
                'nb_versets' => count($versets),
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
}
