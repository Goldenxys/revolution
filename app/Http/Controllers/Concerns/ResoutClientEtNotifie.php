<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\CommandeRecue;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Parametre;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Logique partagée par les formulaires de commande (ancien : type_article
 * libre, toujours final immédiatement ; V2 : demande, finalisée seulement à
 * la validation par la gérante) — reconnaissance/création client, mail de
 * notification et alerte Filament. Extrait de CommandeController pour que
 * les différents chemins ne divergent jamais silencieusement.
 */
trait ResoutClientEtNotifie
{
    /**
     * Recherche/création pure d'un client par téléphone (clé sur les 8
     * derniers chiffres), puis par nom normalisé en repli — sans aucun
     * effet de bord sur ses compteurs de fidélité. `resoudreClient()`
     * (ancien formulaire) et `resoudreProspect()` (V2) s'appuient tous les
     * deux dessus, puis appliquent chacun leur propre logique
     * d'incrémentation.
     */
    private function trouverOuCreerClient(array $donnees): Client
    {
        $cle = Client::cleDepuisTelephone($donnees['telephone']);

        $client = Client::query()->where('cle', $cle)->first();

        if (! $client) {
            $nomNormalise = Client::nomNormalise($donnees['nom']);

            $client = Client::query()
                ->get(['id', 'nom'])
                ->first(fn (Client $candidat) => Client::nomNormalise($candidat->nom) === $nomNormalise);

            if ($client) {
                $client = Client::query()->find($client->id);
            }
        }

        if (! $client) {
            $client = new Client(['cle' => $cle]);
        }

        $client->nom = $donnees['nom'];
        $client->telephone = $donnees['telephone'];

        if (! empty($donnees['email'])) {
            $client->email = $donnees['email'];
        }

        $client->commune = $donnees['commune'];

        return $client;
    }

    /**
     * Ancien formulaire libre (/commande/my-verse, /commande/autre) :
     * comportement inchangé — une commande y est toujours immédiatement
     * finale, donc le compteur de fidélité s'incrémente dès la soumission.
     */
    private function resoudreClient(array $donnees): Client
    {
        $client = $this->trouverOuCreerClient($donnees);

        $client->nb_commandes = ($client->nb_commandes ?? 0) + 1;
        $client->premiere_commande_at = $client->premiere_commande_at ?? now();
        $client->derniere_commande_at = now();
        $client->save();

        return $client;
    }

    /**
     * Formulaire de demande V2 (/commande) : la cliente dépose une demande,
     * pas une commande finale — son compteur de fidélité ne bouge donc pas
     * ici. Il ne s'incrémente qu'à la validation par la gérante
     * (Commande::valider()). On attribue simplement un numero_client dès la
     * première demande, pour la carte de fidélité affichée immédiatement.
     */
    private function resoudreProspect(array $donnees): Client
    {
        $client = $this->trouverOuCreerClient($donnees);
        $client->save();

        if (blank($client->numero_client)) {
            $client->numero_client = $client->genererNumeroClient();
            $client->save();
        }

        return $client;
    }

    /**
     * La commande est déjà enregistrée à ce stade : un incident mail
     * (config SMTP, service indisponible…) ne doit jamais empêcher la
     * cliente d'accéder à sa confirmation et à sa carte de fidélité.
     */
    private function envoyerMailCommande(Commande $commande): void
    {
        try {
            Mail::to(Parametre::emailReception())->queue(new CommandeRecue($commande));
        } catch (Throwable $e) {
            Log::error('Échec de mise en file du mail de commande RÉVOLUTION', [
                'commande' => $commande->reference,
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alerte la gérante dans l'Espace RÉVOLUTION : cloche de notifications
     * Filament (persistante, relisible) + son joué côté navigateur si le
     * tableau de bord est ouvert (resources/js/filament/notification-son.js
     * interroge périodiquement le nombre de notifications non lues).
     *
     * Envoyée avec Notification::sendNow() plutôt que ->sendToDatabase() :
     * la classe de notification de Filament implémente ShouldQueue, donc un
     * envoi normal attend le prochain passage du worker de file d'attente —
     * inutile pour une alerte censée être vue en direct pendant que le
     * tableau de bord est ouvert. Le mail, lui, reste volontairement en
     * file : son délai n'a pas d'importance pour la cliente.
     *
     * @param  string|null  $url  Lien porté par l'action « Voir » de la
     *                            notification — la fiche commande par
     *                            défaut, ou le compositeur pour une
     *                            demande V2 fraîchement déposée.
     */
    private function notifierNouvelleCommande(Commande $commande, ?string $url = null): void
    {
        $commande->loadMissing(['client', 'lignes.article.collection']);

        $libelleCollection = $commande->libelleCollection();
        $doree = $commande->estCollectionMyVerse();
        $url ??= route('filament.admin.resources.commandes.view', $commande);

        try {
            $notification = Notification::make()
                ->title('Nouvelle commande RÉVOLUTION')
                ->body("{$commande->client->nom} — {$libelleCollection}")
                ->icon('heroicon-o-shopping-bag')
                ->iconColor($doree ? 'gold' : 'primary')
                ->actions([
                    NotificationAction::make('voir')
                        ->label('Voir la commande')
                        ->url($url)
                        ->markAsRead(),
                ]);

            NotificationFacade::sendNow(User::all(), $notification->toDatabase());
        } catch (Throwable $e) {
            Log::error('Échec de la notification de nouvelle commande RÉVOLUTION', [
                'commande' => $commande->reference,
                'erreur' => $e->getMessage(),
            ]);
        }
    }
}
