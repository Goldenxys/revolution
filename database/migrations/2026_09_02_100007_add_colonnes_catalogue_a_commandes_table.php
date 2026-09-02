<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extension strictement additive de `commandes` pour le nouveau parcours
     * catalogue : aucune colonne existante n'est renommée ni supprimée, le
     * formulaire actuel (/commande/my-verse, /commande/autre) continue de
     * fonctionner à l'identique.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Montants — NULL sur les commandes qui n'ont pas de prix connu
            // (ancien formulaire, avant backfill) : jamais de montant inventé.
            $table->unsignedInteger('sous_total')->nullable()->after('numero_commande_client');
            $table->unsignedTinyInteger('remise_pourcentage')->nullable()->after('sous_total');
            $table->unsignedInteger('remise_montant')->nullable()->after('remise_pourcentage');
            $table->unsignedInteger('total')->nullable()->after('remise_montant');

            // Suivi de statut, utile pour toutes les commandes (anciennes et
            // nouvelles) — n'existait pas du tout jusqu'ici.
            $table->enum('statut', ['nouvelle', 'confirmee', 'preparation', 'livree', 'annulee'])
                ->default('nouvelle')->after('total');

            $table->text('notes')->nullable()->after('statut');

            // Distingue une commande née du nouveau parcours catalogue d'une
            // commande legacy ou backfillée, sans avoir à deviner via
            // "collection est-elle NULL ?" dans tout le code Filament.
            $table->boolean('utilise_catalogue')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn([
                'sous_total',
                'remise_pourcentage',
                'remise_montant',
                'total',
                'statut',
                'notes',
                'utilise_catalogue',
            ]);
        });
    }
};
