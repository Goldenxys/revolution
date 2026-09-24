<?php

namespace App\Http\Controllers;

use App\Filament\Resources\DemandeResource\Pages\ComposerDemande;
use App\Http\Controllers\Concerns\ResoutClientEtNotifie;
use App\Http\Requests\StoreDemandeRequest;
use App\Mail\DemandeDeposee;
use App\Mail\DemandeRecue;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\Parametre;
use App\Support\LoyaltyCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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
     * Carte de fidélité PNG (nouveau gabarit, resources/loyalty/SPECS.md) —
     * même geste de clôture que la page merci : compte la demande tout
     * juste déposée comme si elle était déjà livrée. Sert la variante
     * « palier débloqué » si ce dépôt fait franchir un palier pair, sinon la
     * variante « progression ». 404 seulement pour une référence inconnue ou
     * une demande qui n'est plus en_attente.
     */
    public function carte(string $reference): Response
    {
        $commande = Commande::with('client')
            ->where('reference', $reference)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        $client = $commande->client;
        $nbProjete = ($client->nb_commandes ?? 0) + 1;
        $palierProjete = (($nbProjete - 1) % 8) + 1;
        $avantageProjete = Client::avantagePourNumero($nbProjete);

        $png = $avantageProjete !== null
            ? LoyaltyCardService::generer($client->nom, $palierProjete, $avantageProjete)
            : LoyaltyCardService::genererProgression(
                $client->nom,
                $palierProjete,
                Client::commandesRestantesPourPalier($palierProjete),
                Client::prochainAvantagePourPalier($palierProjete)
            );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="carte-fidelite-revolution.png"',
            // no-transform : empêche un CDN/proxy intermédiaire de
            // recompresser/redimensionner le PNG — la carte doit rester au
            // format exact du gabarit (2480×3508, A4 300 dpi, SPECS.md).
            'Cache-Control' => 'private, max-age=300, no-transform',
            'X-Robots-Tag' => 'noindex, nofollow',
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
