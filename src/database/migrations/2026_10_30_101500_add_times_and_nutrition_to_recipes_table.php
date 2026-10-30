<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedInteger('prep_time_minutes')->nullable()->after('total_time_minutes');
            $table->unsignedInteger('cook_time_minutes')->nullable()->after('prep_time_minutes');
            $table->json('nutrition')->nullable()->after('num_servings');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['prep_time_minutes', 'cook_time_minutes', 'nutrition']);
        });
    }
};
