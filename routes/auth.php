<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Pas d'inscription publique : le seul compte de l'espace RÉVOLUTION est celui
// de la gérante, créé par le seeder AdminUserSeeder.
//
// Toutes les routes d'authentification vivent sous le chemin secret de
// l'Espace RÉVOLUTION plutôt qu'aux adresses conventionnelles /login,
// /forgot-password, etc. — celles-ci sont les toutes premières que ciblent
// les scripts de brute force automatisés, même sans connaître l'existence
// du site. Les noms de route (route('login'), route('password.request')…)
// ne changent pas : tout le reste de l'application continue de fonctionner
// sans modification.
//
// Sous-segment '/connexion' distinct du chemin du panneau Filament lui-même
// (config('revolution.admin_path')) : Filament enregistre inconditionnellement
// ses propres routes internes (dont /logout pour son menu utilisateur) sous
// ce même chemin — les superposer exactement aurait fait entrer en collision
// nos routes et les siennes (même URI, noms différents), rendant le logout
// du panneau introuvable.
Route::prefix(config('revolution.admin_path').'/connexion')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });

    Route::middleware('auth')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)
            ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});
