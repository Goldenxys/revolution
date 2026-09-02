<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schéma posé dès maintenant ; l'onglet Filament "Photos" (glisser-déposer
     * pour l'ordre) est une phase ultérieure, non bloquante pour le reste.
     */
    public function up(): void
    {
        Schema::create('article_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('chemin');
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_photos');
    }
};
