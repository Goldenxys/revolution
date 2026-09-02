<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // restrictOnDelete : une collection/un type utilisé par un article
            // ne peut pas être supprimé (garde-fou admin), seulement désactivé.
            $table->foreignId('collection_id')->constrained('collections')->restrictOnDelete();
            $table->foreignId('type_article_id')->constrained('types_articles')->restrictOnDelete();

            $table->string('nom');
            $table->string('slug')->unique();
            $table->unsignedInteger('prix');
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('ordre')->default(0);

            // Masquage manuel, distinct de "épuisé" (aucune variante disponible).
            $table->boolean('active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
