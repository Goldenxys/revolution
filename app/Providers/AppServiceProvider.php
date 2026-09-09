<?php

namespace App\Providers;

use App\Events\CommandeValidee;
use App\Listeners\EnvoyerRecuEtNotifierVente;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Reçu PDF + e-mails de validation (V2 §7). Enregistré explicitement
        // plutôt que par auto-découverte, pour que le lien événement →
        // auditeur reste visible et testable.
        Event::listen(CommandeValidee::class, EnvoyerRecuEtNotifierVente::class);
    }
}
