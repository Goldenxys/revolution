<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
