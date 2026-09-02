<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couleurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');

            // Pour afficher une pastille de couleur côté client — une pastille
            // seule (sans nom écrit à côté) est illisible pour un daltonien.
            $table->string('code_hex', 7)->nullable();

            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couleurs');
    }
};
