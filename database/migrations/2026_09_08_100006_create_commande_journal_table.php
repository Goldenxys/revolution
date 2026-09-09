<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne par événement (créée, validée, annulée, email_envoye,
     * pdf_genere, whatsapp_envoye, stock_negatif_evite…) — le jour où une
     * cliente affirme n'avoir jamais reçu son reçu, cette table répond en
     * trois secondes (V2 §3.5). Journal immuable : pas d'updated_at.
     */
    public function up(): void
    {
        Schema::create('commande_journal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('evenement');
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['commande_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_journal');
    }
};
