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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 8)->unique()->index();
            $table->string('nom');
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->string('commune')->nullable();
            $table->unsignedInteger('nb_commandes')->default(0);
            $table->timestamp('premiere_commande_at')->nullable();
            $table->timestamp('derniere_commande_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
