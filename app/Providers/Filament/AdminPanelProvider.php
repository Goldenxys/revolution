<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
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
            // Barre de progression de navigation Livewire (wire:navigate) aux
            // couleurs de la marque plutôt qu'au bleu par défaut.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('<style>:root{--livewire-progress-bar-color:#8E3914;}</style>'),
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
