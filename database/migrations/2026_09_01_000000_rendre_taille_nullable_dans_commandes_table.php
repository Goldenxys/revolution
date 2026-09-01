<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les casquettes n'ont pas de taille : le champ doit pouvoir rester vide
     * pour ce type d'article (« Autre collection »).
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('taille')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('taille')->nullable(false)->change();
        });
    }
};
