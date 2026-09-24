<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\NotificationSonController;
use App\Http\Controllers\CarteFideliteController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\ClientReconnaissanceController;
use App\Http\Controllers\CommandeCatalogueController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecuController;
use Illuminate\Support\Facades\Route;

// Écran 1 — Accueil
Route::view('/', 'accueil')->name('accueil');

// Écran 2 (ancien) — Formulaires de commande à article unique, toujours en
// ligne pendant la transition vers le nouveau parcours catalogue ci-dessous
// (voir revolution-mise-a-jour-catalogue.md § 8 : « ne cassez pas le
// formulaire actuel »). Aucun lien de l'accueil n'y mène plus une fois la
// bascule faite, mais les URLs restent fonctionnelles en secours.
Route::get('/commande/my-verse', [CommandeController::class, 'myVerse'])->name('commande.my-verse');
Route::get('/commande/autre', [CommandeController::class, 'autre'])->name('commande.autre');
Route::post('/commande', [CommandeController::class, 'store'])->name('commande.store');

// Écran 2 (V2) — La demande : deux parcours courts, sans images. La cliente
// dépose une demande, la commande naît à la validation par la gérante
// (revolution-v2-commande-validee-par-la-gerante.md §4).
//   • My Verse : elle indique un ou plusieurs versets (référence + texte).
//     Taille et couleur sont réglées par la gérante à la validation.
//   • Autre collection : elle laisse seulement ses coordonnées et la
//     livraison ; la gérante reprend l'article convenu sur WhatsApp.
Route::get('/commande', [DemandeController::class, 'creerMyVerse'])->name('commande.demande.creer');
Route::get('/commande/autre-collection', [DemandeController::class, 'creerAutre'])->name('commande.demande.autre');
Route::post('/commande/demande', [DemandeController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('commande.demande.store');
Route::get('/commande/demande/{reference}/merci', [DemandeController::class, 'confirmation'])
    ->where('reference', '[A-Z0-9]{6}')
    ->name('commande.demande.merci');

// Ancien parcours catalogue self-service (v1) — laissé en ligne deux
// semaines en secours après la bascule (§11, Phase 6), plus aucun lien
// public n'y mène. À retirer sur accord explicite de la gérante.
Route::get('/commande/catalogue', [CommandeCatalogueController::class, 'creer'])->name('commande.catalogue.creer');
Route::post('/commande/panier', [CommandeCatalogueController::class, 'store'])->name('commande.catalogue.store');
Route::get('/commande/catalogue.json', [CatalogueController::class, 'catalogueJson'])->name('commande.catalogue.json');

// Écran 3 — Confirmation + carte de fidélité (commune aux deux parcours)
Route::get('/commande/{reference}', [CommandeController::class, 'show'])
    ->where('reference', '[A-Z0-9]{6}')
    ->name('commande.confirmation');

// Reçu PDF public d'une commande validée (§7.3) — lien porté par un jeton,
// non indexé, valable 90 jours.
Route::get('/recu/{token}', [RecuController::class, 'afficher'])
    ->middleware('throttle:30,1')
    ->name('recu.afficher');

// Téléchargement de la carte de fidélité — même principe que le reçu :
// lien porté par un jeton, jamais un id devinable.
Route::get('/fidelite/{token}', [CarteFideliteController::class, 'telecharger'])
    ->middleware('throttle:30,1')
    ->name('fidelite.telecharger');

// Reconnaissance client en direct (formulaire)
Route::get('/client/reconnaissance', ClientReconnaissanceController::class)
    ->middleware('throttle:20,1')
    ->name('client.reconnaissance');

// Informations légales
Route::view('/mentions-legales', 'legal.mentions-legales')->name('legal.mentions-legales');
Route::view('/confidentialite', 'legal.confidentialite')->name('legal.confidentialite');

// Compte de la gérante (Breeze) — l'Espace RÉVOLUTION lui-même vit sous le
// chemin secret défini dans config('revolution.admin_path') (Filament).
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Exports CSV de l'Espace RÉVOLUTION (déclenchés depuis le tableau de bord).
    Route::prefix(config('revolution.admin_path'))->group(function () {
        Route::get('/export/commandes', [ExportController::class, 'commandesJour'])->name('admin.export.commandes');
        Route::get('/export/clients', [ExportController::class, 'clients'])->name('admin.export.clients');

        // Compteur de notifications non lues, sondé en JS pour déclencher le
        // son d'alerte de nouvelle commande (resources/js/notification-son.js).
        Route::get('/notifications/compte', [NotificationSonController::class, 'compte'])
            ->name('admin.notifications.compte');
    });
});

require __DIR__.'/auth.php';
