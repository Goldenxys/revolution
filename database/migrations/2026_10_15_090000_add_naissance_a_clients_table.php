<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedTinyInteger('naissance_jour')->nullable()->after('email');
            $table->unsignedTinyInteger('naissance_mois')->nullable()->after('naissance_jour');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['naissance_jour', 'naissance_mois']);
        });
    }
};
