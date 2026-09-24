<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne par palier de fidélité réellement débloqué (ancien
     * formulaire libre, seul parcours où une commande est finale dès le
     * clic du client — voir CommandeController::store()). `numero_commande`
     * est le rang absolu de la cliente (2, 4, 6, 8, 10, 12…) : les paliers
     * se répètent tous les 8 commandes, donc c'est lui, et non `palier`
     * seul (2/4/6/8), qui identifie un déblocage précis et garantit qu'il
     * n'a jamais lieu deux fois pour la même cliente.
     */
    public function up(): void
    {
        Schema::create('reductions_fidelite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commande_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero_commande');
            $table->unsignedTinyInteger('palier');
            $table->unsignedTinyInteger('pourcentage');
            $table->string('chemin_fichier');
            $table->string('token', 40)->unique();
            $table->timestamp('utilisee_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'numero_commande']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reductions_fidelite');
    }
};
