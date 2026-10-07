<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carte anniversaire en option (+500 F par défaut, voir
     * config('revolution.prix_carte_anniversaire')), proposée par la
     * gérante au compositeur — un supplément hors chiffre d'affaires, sur
     * le même principe que frais_livraison : le montant facturé est figé
     * sur la commande au moment de la validation, jamais recalculé après
     * coup même si le tarif change ensuite.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->unsignedInteger('frais_carte_anniversaire')->default(0)->after('frais_livraison');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn('frais_carte_anniversaire');
        });
    }
};
