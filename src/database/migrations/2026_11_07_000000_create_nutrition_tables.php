<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_sources', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->string('version', 50);
            $table->string('license');
            $table->string('url')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_source_id')->constrained('nutrition_sources')->cascadeOnDelete();
            $table->string('source_food_id', 100)->nullable();
            $table->string('name');
            $table->string('name_en');
            $table->string('name_fr');
            $table->string('category', 60)->index();
            $table->string('preparation_state', 40)->default('raw')->index(); // raw, cooked, boiled, roasted, baked, canned, etc.
            $table->decimal('density_g_per_ml', 8, 4)->nullable(); // e.g. 0.92 for olive oil, 1.03 for milk
            $table->decimal('piece_weight_g', 8, 2)->nullable(); // e.g. 50g for 1 medium egg, 180g for 1 apple
            $table->decimal('cup_weight_g', 8, 2)->nullable(); // e.g. 120g for 1 cup flour, 200g for 1 cup sugar
            $table->decimal('tbsp_weight_g', 8, 2)->nullable(); // e.g. 13.5g for 1 tbsp oil
            $table->decimal('tsp_weight_g', 8, 2)->nullable(); // e.g. 5g for 1 tsp salt
            $table->json('aliases')->nullable(); // search keywords/synonyms
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['name_en']);
            $table->index(['name_fr']);
        });

        Schema::create('food_nutrients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->decimal('calories_kcal', 8, 2);
            $table->decimal('protein_g', 8, 2);
            $table->decimal('carbohydrates_g', 8, 2);
            $table->decimal('fat_g', 8, 2);
            $table->decimal('fiber_g', 8, 2)->nullable();
            $table->decimal('sugar_g', 8, 2)->nullable();
            $table->decimal('saturated_fat_g', 8, 2)->nullable();
            $table->decimal('sodium_mg', 8, 2)->nullable();
            $table->boolean('data_per_100g')->default(true);
            $table->decimal('data_completeness', 4, 2)->default(1.0);
            $table->timestamps();

            $table->index(['food_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_nutrients');
        Schema::dropIfExists('foods');
        Schema::dropIfExists('nutrition_sources');
    }
};
