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
 * Le formulaire est court, textuel, sans aucune image d'article. Le client
 * ne choisit plus d'article : il indique s'il veut un tee-shirt My Verse
 * (et fournit alors son verset / sa taille / sa couleur) ou un autre
 * article de la collection — la gérante compose la commande depuis son
 * panneau à la validation. Aucun total ferme n'est enregistré.
 */
class DemandeController extends Controller
{
    use ResoutClientEtNotifie;

    public function creer(): View
    {
        return view('commande.demande', [
            'communes' => config('revolution.communes'),
        ]);
    }

    public function store(StoreDemandeRequest $request): RedirectResponse
    {
        $donnees = $request->validated();
        $estMyVerse = $donnees['collection'] === 'my_verse';

        $commande = DB::transaction(function () use ($donnees, $estMyVerse) {
            $client = $this->resoudreProspect($donnees);

            // Frais recalculés côté serveur, jamais depuis le formulaire.
            $fraisLivraison = config('revolution.communes')[$donnees['commune']];

            $commande = Commande::create([
                'client_id' => $client->id,
                'collection' => $donnees['collection'],
                // Détails My Verse fournis par le client — figés tels quels,
                // ce sont la trace de sa demande (§3.1). Vides pour « autre » :
                // la gérante reprend l'article convenu sur WhatsApp.
                'taille' => $estMyVerse ? ($donnees['taille'] ?? null) : null,
                'couleur' => $estMyVerse ? ($donnees['couleur'] ?? null) : null,
                'verset_reference' => $estMyVerse ? ($donnees['verset_reference'] ?? null) : null,
                'verset_texte' => $estMyVerse ? ($donnees['verset_texte'] ?? null) : null,
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
