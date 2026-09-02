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
 * Logique partagée par les deux formulaires de commande (ancien : type_article
 * libre ; nouveau : catalogue) — reconnaissance client, mail de notification
 * et alerte Filament. Extrait de CommandeController pour que les deux
 * chemins ne divergent jamais silencieusement pendant la période où les
 * deux formulaires coexistent (voir routes/web.php).
 */
trait ResoutClientEtNotifie
{
    /**
     * Retrouve un client existant par téléphone (clé sur les 8 derniers
     * chiffres), puis par nom normalisé en repli, sinon en crée un nouveau.
     * Incrémente systématiquement son compteur de commandes.
     */
    private function resoudreClient(array $donnees): Client
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
        $client->nb_commandes = ($client->nb_commandes ?? 0) + 1;
        $client->premiere_commande_at = $client->premiere_commande_at ?? now();
        $client->derniere_commande_at = now();
        $client->save();

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
     */
    private function notifierNouvelleCommande(Commande $commande): void
    {
        $commande->loadMissing(['client', 'lignes.article.collection']);

        [$libelleCollection, $doree] = $this->libelleEtCouleurCollection($commande);

        try {
            $notification = Notification::make()
                ->title('Nouvelle commande RÉVOLUTION')
                ->body("{$commande->client->nom} — {$libelleCollection}")
                ->icon('heroicon-o-shopping-bag')
                ->iconColor($doree ? 'gold' : 'primary')
                ->actions([
                    NotificationAction::make('voir')
                        ->label('Voir la commande')
                        ->url(route('filament.admin.resources.commandes.view', $commande))
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

    /**
     * @return array{0: string, 1: bool} libellé de collection à afficher, et
     *                                    si l'icône doit être dorée (My verse)
     */
    private function libelleEtCouleurCollection(Commande $commande): array
    {
        if ($commande->utilise_catalogue) {
            $collection = $commande->lignes->first()?->article?->collection;

            return [$collection?->nom ?? 'RÉVOLUTION', $collection?->slug === 'my_verse'];
        }

        return [$commande->estMyVerse() ? 'MY VERSE' : 'Autre collection', $commande->estMyVerse()];
    }
}
