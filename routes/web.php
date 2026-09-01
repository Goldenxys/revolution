<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\NotificationSonController;
use App\Http\Controllers\ClientReconnaissanceController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Écran 1 — Accueil
Route::view('/', 'accueil')->name('accueil');

// Écran 2 — Formulaires de commande
Route::get('/commande/my-verse', [CommandeController::class, 'myVerse'])->name('commande.my-verse');
Route::get('/commande/autre', [CommandeController::class, 'autre'])->name('commande.autre');
Route::post('/commande', [CommandeController::class, 'store'])->name('commande.store');

// Écran 3 — Confirmation + carte de fidélité
Route::get('/commande/{reference}', [CommandeController::class, 'show'])
    ->where('reference', '[A-Z0-9]{6}')
    ->name('commande.confirmation');

// Reconnaissance client en direct (formulaire)
Route::get('/client/reconnaissance', ClientReconnaissanceController::class)
    ->middleware('throttle:20,1')
    ->name('client.reconnaissance');

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
