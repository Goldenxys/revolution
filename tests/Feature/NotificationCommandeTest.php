<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function donneesCommande(array $override = []): array
    {
        return array_merge([
            'collection' => 'autre',
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'commune' => 'Yopougon',
            'mode_livraison' => 'livreur',
            'taille' => 'M',
            'type_article' => 'Pull',
            'nom_article' => 'Couronne d\'épines',
        ], $override);
    }

    public function test_une_commande_cree_une_notification_pour_la_gerante(): void
    {
        Mail::fake();

        $gerante = User::factory()->create();

        $this->assertSame(0, $gerante->unreadNotifications()->count());

        $this->post(route('commande.store'), $this->donneesCommande());

        $gerante->refresh();
        $this->assertSame(1, $gerante->unreadNotifications()->count());

        $notification = $gerante->unreadNotifications()->first();
        $this->assertSame('Nouvelle commande RÉVOLUTION', $notification->data['title']);
        $this->assertStringContainsString('Aya Kouassi', $notification->data['body']);
    }

    public function test_la_notification_narrive_pas_en_file_dattente(): void
    {
        // La classe de notification de Filament implémente ShouldQueue par
        // défaut : sans précaution, la notification attendrait le prochain
        // passage du worker de file d'attente (le cron, sur cet hébergement
        // mutualisé) au lieu d'apparaître immédiatement sur le tableau de
        // bord ouvert. Elle doit être écrite tout de suite, sans passer par
        // la table jobs — voir CommandeController::notifierNouvelleCommande().
        Mail::fake();

        User::factory()->create();

        $this->post(route('commande.store'), $this->donneesCommande());

        $tachesEnAttente = DB::table('jobs')
            ->where('payload', 'like', '%DatabaseNotification%')
            ->count();

        $this->assertSame(0, $tachesEnAttente);
    }

    public function test_le_compteur_de_notifications_augmente_apres_une_commande(): void
    {
        Mail::fake();

        $gerante = User::factory()->create();

        $avant = $this->actingAs($gerante)
            ->get(route('admin.notifications.compte'))
            ->json('compte');

        $this->assertSame(0, $avant);

        $this->post(route('commande.store'), $this->donneesCommande());

        $apres = $this->actingAs($gerante)
            ->get(route('admin.notifications.compte'))
            ->json('compte');

        $this->assertSame(1, $apres);
    }
}
