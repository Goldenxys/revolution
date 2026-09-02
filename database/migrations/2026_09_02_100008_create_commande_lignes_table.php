<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();

            // NULL pour les lignes backfillées depuis l'ancien format, ou si la
            // fiche article est supprimée plus tard : l'historique de vente ne
            // dépend jamais de l'existence continue de l'article.
            $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();

            // Copies figées au moment de l'achat : si la gérante change un
            // prix ou un nom plus tard, les commandes déjà passées gardent
            // leurs valeurs d'origine.
            $table->string('article_nom');
            $table->string('taille_libelle')->nullable();
            $table->string('couleur_nom')->nullable();
            $table->unsignedSmallInteger('quantite')->default(1);
            $table->unsignedInteger('prix_unitaire');

            $table->text('verset')->nullable();
            $table->string('modele')->nullable();

            $table->timestamps();

            $table->index('commande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_lignes');
    }
};
