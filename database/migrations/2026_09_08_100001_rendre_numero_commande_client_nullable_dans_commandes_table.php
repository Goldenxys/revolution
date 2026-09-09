<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une commande V2 en attente (statut en_attente) ne connaît pas encore
     * son rang de fidélité : il ne se calcule qu'à la validation par la
     * gérante (Commande::valider()). Reste peuplé immédiatement pour le
     * formulaire libre, inchangé.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->unsignedInteger('numero_commande_client')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->unsignedInteger('numero_commande_client')->nullable(false)->change();
        });
    }
};
