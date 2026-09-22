<?php

namespace App\Providers;

use App\Events\CommandeLivree;
use App\Events\CommandeValidee;
use App\Listeners\EnvoyerRecuEtNotifierVente;
use App\Listeners\NotifierVenteLivree;
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
        // Reçu PDF + e-mail cliente à la validation (V2 §7), notification
        // « vente réalisée » à la gérante à la livraison confirmée.
        // Enregistré explicitement plutôt que par auto-découverte, pour que
        // le lien événement → auditeur reste visible et testable.
        Event::listen(CommandeValidee::class, EnvoyerRecuEtNotifierVente::class);
        Event::listen(CommandeLivree::class, NotifierVenteLivree::class);
    }
}
