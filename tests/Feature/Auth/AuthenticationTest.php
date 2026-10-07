<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/'.config('revolution.admin_path'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * Protection contre le brute force sur la connexion de l'Espace
     * RÉVOLUTION (App\Http\Requests\Auth\LoginRequest::ensureIsNotRateLimited()) :
     * 5 tentatives échouées par couple email+IP, puis verrouillage — même
     * avec le bon mot de passe au 6ᵉ essai, tant que le délai n'est pas
     * écoulé.
     */
    public function test_le_rate_limiter_bloque_la_connexion_apres_5_tentatives_echouees(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'mauvais-mot-de-passe',
            ]);
        }

        $this->assertGuest();

        // 6ᵉ tentative, cette fois avec le BON mot de passe : refusée quand
        // même, verrouillée par le rate limiter avant même de vérifier le
        // mot de passe.
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
