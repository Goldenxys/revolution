<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table plutôt qu'énumération : ajouter une taille (S, 3XL...) devient
     * une ligne à créer depuis l'admin, pas une migration.
     */
    public function up(): void
    {
        Schema::create('tailles', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailles');
    }
};
