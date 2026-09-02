<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();

            // NULL quand le type de l'article ne gère pas cet attribut (ex.
            // taille_id NULL pour un tote bag). restrictOnDelete : une
            // taille/couleur référencée par une variante ne peut pas être
            // supprimée, seulement désactivée.
            $table->foreignId('taille_id')->nullable()->constrained('tailles')->restrictOnDelete();
            $table->foreignId('couleur_id')->nullable()->constrained('couleurs')->restrictOnDelete();

            $table->boolean('disponible')->default(true);
            $table->integer('stock')->nullable();
            $table->timestamps();

            // Pas d'index unique(article_id, taille_id, couleur_id) : NULL
            // rend un tel index inopérant (MySQL/Postgres traitent deux NULL
            // comme différents). Unicité vérifiée en application, voir
            // ArticleVariante::existeDeja().
            $table->index(['article_id', 'disponible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_variantes');
    }
};
