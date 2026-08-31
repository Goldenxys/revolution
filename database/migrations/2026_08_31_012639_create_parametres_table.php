<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ligne unique de réglages modifiables par la gérante depuis
     * l'Espace RÉVOLUTION (Filament) : adresse de réception des commandes,
     * clé du service d'envoi de mail, code d'accès.
     */
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('email_reception');
            $table->string('mail_cle')->nullable();
            $table->string('code_acces');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
