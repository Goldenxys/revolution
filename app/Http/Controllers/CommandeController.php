<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommandeRequest;
use App\Mail\CommandeRecue;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Parametre;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class CommandeController extends Controller
{
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
                'taille' => $donnees['taille'],
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

        // La commande est déjà enregistrée à ce stade : un incident mail
        // (config SMTP, service indisponible…) ne doit jamais empêcher la
        // cliente d'accéder à sa confirmation et à sa carte de fidélité.
        try {
            Mail::to(Parametre::emailReception())->queue(new CommandeRecue($commande));
        } catch (Throwable $e) {
            Log::error('Échec de mise en file du mail de commande RÉVOLUTION', [
                'commande' => $commande->reference,
                'erreur' => $e->getMessage(),
            ]);
        }

        $this->notifierNouvelleCommande($commande);

        return redirect()->route('commande.confirmation', $commande->reference);
    }

    /**
     * Alerte la gérante dans l'Espace RÉVOLUTION : cloche de notifications
     * Filament (persistante, relisible) + son joué côté navigateur si le
     * tableau de bord est ouvert (resources/js/notification-son.js interroge
     * périodiquement le nombre de notifications non lues).
     */
    private function notifierNouvelleCommande(Commande $commande): void
    {
        $commande->loadMissing('client');
        $collection = $commande->estMyVerse() ? 'MY VERSE' : 'Autre collection';

        try {
            Notification::make()
                ->title('Nouvelle commande RÉVOLUTION')
                ->body("{$commande->client->nom} — {$collection}")
                ->icon('heroicon-o-shopping-bag')
                ->iconColor($commande->estMyVerse() ? 'gold' : 'primary')
                ->actions([
                    NotificationAction::make('voir')
                        ->label('Voir la commande')
                        ->url(route('filament.admin.resources.commandes.view', $commande))
                        ->markAsRead(),
                ])
                ->sendToDatabase(User::all());
        } catch (Throwable $e) {
            Log::error('Échec de la notification de nouvelle commande RÉVOLUTION', [
                'commande' => $commande->reference,
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    public function show(string $reference): View
    {
        $commande = Commande::with('client')->where('reference', $reference)->firstOrFail();

        return view('commande.confirmation', [
            'commande' => $commande,
            'client' => $commande->client,
        ]);
    }

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
}
