<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Casse le cache navigateur/CDN de NOS assets custom (ci-dessous) à
        // chaque déploiement : sans ça, le `?v=` que Filament ajoute à
        // l'URL du script reste figé sur la version du paquet
        // filament/support, qui ne bouge jamais quand on modifie notre
        // propre JS — le CDN de l'hébergeur (max-age 7 jours) sert alors
        // l'ancienne version pendant une semaine après chaque mise à jour.
        FilamentAsset::appVersion(static::versionApplicative());

        // Son d'alerte de nouvelle commande (resources/js/filament/notification-son.js)
        // et visite guidée (resources/js/filament/revo-tour.js) : publiés par
        // `php artisan filament:assets` et chargés nativement dans le panneau,
        // pas via notre propre bundle Vite (que Filament n'utilise pas).
        // Package 'app' (défaut) : c'est la seule valeur pour laquelle
        // Filament utilise appVersion() ci-dessus plutôt que la version d'un
        // paquet Composer.
        FilamentAsset::register([
            Js::make('revo-notification-son', __DIR__.'/../../../resources/js/filament/notification-son.js'),
            Js::make('revo-tour', __DIR__.'/../../../resources/js/filament/revo-tour.js'),
        ]);
    }

    /**
     * Le SHA du commit déployé, lu directement dans .git (aucun accès
     * `exec`/`proc_open` : désactivés chez notre hébergeur mutualisé) —
     * change donc automatiquement à chaque déploiement, sans étape manuelle.
     * Replié sur la version de Laravel si .git est absent (ex. déploiement
     * par archive plutôt que par clone).
     */
    private static function versionApplicative(): string
    {
        try {
            $gitDir = base_path('.git');
            $head = trim((string) @file_get_contents($gitDir.'/HEAD'));

            $sha = str_starts_with($head, 'ref: ')
                ? trim((string) @file_get_contents($gitDir.'/'.substr($head, 5)))
                : $head;

            if (preg_match('/^[0-9a-f]{7,40}$/', $sha)) {
                return substr($sha, 0, 12);
            }
        } catch (\Throwable) {
            // .git illisible : on retombe sur la version de l'application ci-dessous.
        }

        return app()->version();
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            // Chemin volontairement non-devinable au lieu de /admin — voir
            // config/revolution.php (admin_path).
            ->path(config('revolution.admin_path'))
            // L'espace gérante n'a pas de page de connexion Filament : elle passe
            // par l'écran de connexion Breeze, garde web classique — servi
            // sous ce même chemin secret (routes/auth.php), jamais à /login.
            ->login(false)
            ->authGuard('web')
            ->brandName('RÉVOLUTION')
            ->favicon(asset('favicon/favicon-32x32.png'))
            ->colors([
                'primary' => Color::hex('#8E3914'),
                'gold' => Color::hex('#AB6715'),
                'gray' => Color::Stone,
                'danger' => Color::hex('#B3261E'),
                'success' => Color::hex('#3F7D4A'),
            ])
            ->font('Poppins')
            // Thème Tailwind dédié au panneau (resources/css/filament/admin/theme.css) :
            // nécessaire pour que les classes utilitaires custom des vues
            // Filament (cartes du tableau de bord, etc.) soient réellement
            // compilées — le bundle par défaut de Filament ne les connaît pas.
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Cloche de notifications (nouvelles commandes) dans la barre du
            // haut, avec relecture de l'historique en base.
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            // Barre de progression de navigation Livewire (wire:navigate) aux
            // couleurs de la marque plutôt qu'au bleu par défaut.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('<style>:root{--livewire-progress-bar-color:#8E3914;}</style>'),
            )
            // Marqueur lu par le script enregistré dans boot() ci-dessus :
            // l'URL à sonder pour le compteur de notifications non lues.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): HtmlString => new HtmlString(
                    '<div data-revo-notifications-compte-url="'.route('admin.notifications.compte').'"></div>'
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // Pas de Pages\Dashboard::class : le tableau de bord est notre
            // page personnalisée App\Filament\Pages\TableauDeBord (slug '/'),
            // auto-découverte ci-dessous.
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
