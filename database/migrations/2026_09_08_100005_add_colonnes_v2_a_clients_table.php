<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `premiere_commande_at`/`derniere_commande_at` existent déjà depuis la
     * V1 — seule leur sémantique change (première/dernière commande
     * VALIDÉE, recalculée par la migration de données et par
     * Commande::valider()/annuler()), pas leur schéma.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Passe de prospect à client à la première commande validée
            // (V2 §3.3) — une personne qui dépose des demandes sans jamais
            // rien voir validé reste prospect, sans avantage fidélité.
            $table->enum('statut', ['prospect', 'client'])->default('prospect')->after('cle');

            $table->string('numero_client')->nullable()->unique()->after('statut');

            // Dénormalisé pour l'affichage (tableau de bord, fiche client) :
            // somme des total_articles des commandes validées.
            $table->unsignedInteger('ca_cumule')->default(0)->after('nb_commandes');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['statut', 'numero_client', 'ca_cumule']);
        });
    }
};
