<?php

namespace Tests\Feature\V2;

use App\Filament\Pages\Reglages;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le changement de mot de passe était déjà possible via /profile (Breeze),
 * mais rien dans le panneau n'y menait — la gérante n'avait aucun moyen de
 * le trouver. Désormais accessible directement depuis « Réglages ».
 */
class ReglagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_reglages_se_charge(): void
    {
        $gerante = User::factory()->create();

        $this->actingAs($gerante)
            ->get('/'.config('revolution.admin_path').'/reglages')
            ->assertOk();
    }

    public function test_changer_le_mot_de_passe_avec_le_bon_mot_de_passe_actuel(): void
    {
        $gerante = User::factory()->create(['password' => Hash::make('ancien-mdp-123')]);

        Livewire::actingAs($gerante)
            ->test(Reglages::class)
            ->fillForm([
                'current_password' => 'ancien-mdp-123',
                'password' => 'nouveau-mdp-456',
                'password_confirmation' => 'nouveau-mdp-456',
            ], 'passwordForm')
            ->call('changerMotDePasse')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('nouveau-mdp-456', $gerante->fresh()->password));
    }

    public function test_changer_le_mot_de_passe_refuse_un_mauvais_mot_de_passe_actuel(): void
    {
        $gerante = User::factory()->create(['password' => Hash::make('ancien-mdp-123')]);

        Livewire::actingAs($gerante)
            ->test(Reglages::class)
            ->fillForm([
                'current_password' => 'mauvais-mdp',
                'password' => 'nouveau-mdp-456',
                'password_confirmation' => 'nouveau-mdp-456',
            ], 'passwordForm')
            ->call('changerMotDePasse')
            ->assertHasFormErrors(['current_password'], formName: 'passwordForm');

        $this->assertTrue(Hash::check('ancien-mdp-123', $gerante->fresh()->password));
    }

    public function test_changer_le_mot_de_passe_refuse_une_confirmation_qui_ne_correspond_pas(): void
    {
        $gerante = User::factory()->create(['password' => Hash::make('ancien-mdp-123')]);

        Livewire::actingAs($gerante)
            ->test(Reglages::class)
            ->fillForm([
                'current_password' => 'ancien-mdp-123',
                'password' => 'nouveau-mdp-456',
                'password_confirmation' => 'autre-chose',
            ], 'passwordForm')
            ->call('changerMotDePasse')
            ->assertHasFormErrors(['password'], formName: 'passwordForm');

        $this->assertTrue(Hash::check('ancien-mdp-123', $gerante->fresh()->password));
    }
}
