<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une commande née du parcours catalogue (utilise_catalogue = true) ne
     * renseigne pas `collection` : sa/ses collection(s) se déduisent des
     * lignes (commande_lignes → articles → collections), potentiellement
     * plusieurs si le panier contient des articles de collections
     * différentes. Cette colonne reste utilisée telle quelle par les
     * commandes de l'ancien formulaire (my_verse / autre).
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('collection', ['my_verse', 'autre'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('collection', ['my_verse', 'autre'])->nullable(false)->change();
        });
    }
};
