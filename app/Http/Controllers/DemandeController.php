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
use App\Models\Couleur;
use App\Models\Parametre;
use App\Models\Taille;
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
 *     texte), avec la taille/couleur souhaitées pour chacun (pas de lien au
 *     stock : My Verse est fabriqué à la demande). Le modèle exact reste
 *     choisi par la gérante à la validation.
 *   • Autre collection — la cliente nomme un ou plusieurs articles (avec
 *     recherche/autocomplétion sur le catalogue, taille/couleur, quantité),
 *     ou décrit un article hors catalogue. La gérante reprend, complète ou
 *     corrige tout ceci à la validation — rien de tout cela n'est engageant.
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
            ...$this->donneesFormulaire(),
        ]);
    }

    public function creerAutre(): View
    {
        return view('commande.demande', [
            'type' => 'autre',
            ...$this->donneesFormulaire(),
        ]);
    }

    /**
     * Données communes aux deux entrées du formulaire — tailles/couleurs
     * servent à la fois aux versets My Verse et aux lignes d'articles
     * « Autre collection » (référentiels globaux, ouverts, non filtrés par
     * stock côté formulaire : la disponibilité réelle reste vérifiée par la
     * gérante au compositeur).
     *
     * @return array<string, mixed>
     */
    private function donneesFormulaire(): array
    {
        return [
            'communes' => config('revolution.communes'),
            'tailles' => Taille::query()->actives()->orderBy('ordre')->get(['id', 'libelle']),
            'couleurs' => Couleur::query()->actives()->orderBy('ordre')->get(['id', 'nom']),
        ];
    }

    public function store(StoreDemandeRequest $request): RedirectResponse
    {
        $donnees = $request->validated();
        $estMyVerse = $donnees['collection'] === 'my_verse';

        $versets = $estMyVerse
            ? collect($donnees['versets'] ?? [])
                ->map(function (array $v) {
                    $taille = filled($v['taille_id'] ?? null) ? Taille::find($v['taille_id']) : null;
                    $couleur = filled($v['couleur_id'] ?? null) ? Couleur::find($v['couleur_id']) : null;

                    return [
                        'reference' => filled($v['reference'] ?? null) ? trim($v['reference']) : null,
                        'texte' => filled($v['texte'] ?? null) ? trim($v['texte']) : null,
                        'taille_id' => $taille?->id,
                        'taille_libelle' => $taille?->libelle,
                        'couleur_id' => $couleur?->id,
                        'couleur_nom' => $couleur?->nom,
                    ];
                })
                ->values()
                ->all()
            : [];

        // Lignes d'articles « Autre collection » — un souhait, jamais
        // engageant : la gérante repique/complète/corrige tout ceci dans
        // le compositeur (ComposerDemande::lignesInitiales()), stock inclus.
        // Une ligne sans nom (répéteur laissé vide) est ignorée.
        $articles = ! $estMyVerse
            ? collect($donnees['articles'] ?? [])
                ->filter(fn (array $a) => filled($a['nom'] ?? null))
                ->map(function (array $a) {
                    $taille = filled($a['taille_id'] ?? null) ? Taille::find($a['taille_id']) : null;
                    $couleur = filled($a['couleur_id'] ?? null) ? Couleur::find($a['couleur_id']) : null;

                    return [
                        'nom' => trim($a['nom']),
                        'article_id' => filled($a['article_id'] ?? null) ? (int) $a['article_id'] : null,
                        'taille_id' => $taille?->id,
                        'taille_libelle' => $taille?->libelle,
                        'couleur_id' => $couleur?->id,
                        'couleur_nom' => $couleur?->nom,
                        'quantite' => max(1, (int) ($a['quantite'] ?? 1)),
                    ];
                })
                ->values()
                ->all()
            : [];

        // Lien de reprise (ComposerDemande) : la cliente renvoie le même
        // formulaire, cette fois avec la référence de sa demande encore
        // en_attente — on la met à jour au lieu d'en créer une nouvelle.
        $commandeExistante = filled($donnees['reference'] ?? null)
            ? Commande::where('reference', $donnees['reference'])->where('statut', 'en_attente')->first()
            : null;

        $commande = DB::transaction(function () use ($donnees, $estMyVerse, $versets, $articles, $commandeExistante) {
            $client = $this->resoudreProspect($donnees);

            // Frais recalculés côté serveur, jamais depuis le formulaire.
            $fraisLivraison = config('revolution.communes')[$donnees['commune']];

            $attributs = [
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
                    : ['collection' => 'autre', 'articles' => $articles],
                'commune' => $donnees['commune'],
                'frais_livraison' => $fraisLivraison,
                'quartier' => $donnees['quartier'] ?? null,
                'mode_livraison' => $donnees['mode_livraison'],
                'date_souhaitee' => $donnees['date_souhaitee'] ?? null,
                'heure_souhaitee' => $donnees['heure_souhaitee'] ?? null,
                'message_client' => $donnees['precisions'] ?? null,
            ];

            if ($commandeExistante) {
                $commandeExistante->update($attributs);
                $commande = $commandeExistante;
                $evenement = 'modifiee_par_cliente';
            } else {
                $commande = Commande::create($attributs + [
                    'statut' => 'en_attente',
                    // Aucune vente à ce stade : tous les totaux restent à zéro.
                    'sous_total' => 0,
                    'remise_pourcentage' => 0,
                    'remise_montant' => 0,
                    'total' => 0,
                    'total_articles' => 0,
                    'total_a_payer' => 0,
                ]);
                $evenement = 'creee';
            }

            CommandeJournal::consigner($commande, $evenement, [
                'canal' => 'formulaire_demande_v2',
                'collection' => $donnees['collection'],
                'nb_versets' => count($versets),
                'nb_articles' => count($articles),
            ]);

            return $commande;
        });

        if ($commandeExistante) {
            $this->notifierGeranteMiseAJour($commande);
        } else {
            $this->notifierGerante($commande);
            $this->accuserReceptionCliente($commande);
        }

        return redirect()->route('commande.demande.merci', $commande->reference);
    }

    /**
     * Rouvre le formulaire de demande, pré-rempli avec tout ce que la
     * cliente a déjà saisi — suivi du lien de reprise envoyé par la
     * gérante (ComposerDemande) si elle s'est trompée ou a oublié un
     * article. Redirige vers l'accueil, sans pré-remplissage, si la
     * demande est introuvable ou déjà composée/validée : rien à reprendre.
     */
    public function reprendre(string $reference): View|RedirectResponse
    {
        $commande = Commande::with('client')->where('reference', $reference)->first();

        if (! $commande || $commande->statut !== 'en_attente') {
            return redirect()
                ->route('accueil')
                ->with('info', 'Cette demande a déjà été traitée. Vous pouvez en déposer une nouvelle.');
        }

        $client = $commande->client;
        $souhaits = $commande->souhaits_client ?? [];

        session()->flashInput([
            'reference' => $commande->reference,
            'nom' => $client->nom,
            'telephone' => $client->telephone,
            'email' => $client->email,
            'commune' => $commande->commune,
            'quartier' => $commande->quartier,
            'mode_livraison' => $commande->mode_livraison,
            'date_souhaitee' => optional($commande->date_souhaitee)->format('Y-m-d'),
            'heure_souhaitee' => optional($commande->heure_souhaitee)->format('H:i'),
            'precisions' => $commande->message_client,
            'versets' => $souhaits['versets'] ?? null,
            'articles' => $souhaits['articles'] ?? null,
        ]);

        return redirect()->route($commande->estMyVerse() ? 'commande.demande.creer' : 'commande.demande.autre');
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
     * Alerte la gérante qu'une demande qu'elle connaît déjà vient d'être
     * modifiée par la cliente (lien de reprise) — cloche Filament
     * seulement, titre adapté pour ne pas laisser croire à une commande
     * inédite. Pas de nouvel accusé de réception : la cliente vient d'agir
     * volontairement, inutile de le lui confirmer par un second e-mail.
     */
    private function notifierGeranteMiseAJour(Commande $commande): void
    {
        $urlCompositeur = ComposerDemande::getUrl(['record' => $commande]);

        $this->notifierNouvelleCommande($commande, $urlCompositeur, 'Demande modifiée par la cliente');
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
