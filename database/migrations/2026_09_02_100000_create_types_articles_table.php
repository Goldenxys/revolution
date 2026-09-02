<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_articles', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();

            // Pilotent l'affichage du formulaire public et de la matrice de
            // disponibilité admin : un tote bag n'a pas de taille, par exemple.
            $table->boolean('gere_tailles')->default(true);
            $table->boolean('gere_couleurs')->default(true);

            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_articles');
    }
};
