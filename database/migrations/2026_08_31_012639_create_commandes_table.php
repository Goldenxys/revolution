<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 6)->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('collection', ['my_verse', 'autre']);

            // Autre collection
            $table->string('type_article')->nullable();
            $table->string('nom_article')->nullable();

            // Communs
            $table->string('taille');
            $table->string('couleur')->nullable();

            // MY VERSE
            $table->string('verset_reference')->nullable();
            $table->text('verset_texte')->nullable();

            // Livraison
            $table->string('commune');
            $table->unsignedInteger('frais_livraison');
            $table->string('quartier')->nullable();
            $table->enum('mode_livraison', ['yango', 'livreur']);
            $table->date('date_souhaitee')->nullable();
            $table->time('heure_souhaitee')->nullable();

            // Fidélité
            $table->unsignedInteger('numero_commande_client');

            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
