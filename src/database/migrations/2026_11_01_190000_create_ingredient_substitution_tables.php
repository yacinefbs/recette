<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->json('aliases')->nullable();
            // Only set when the density is well established; volume <-> weight conversions rely on it.
            $table->decimal('grams_per_cup', 8, 2)->nullable();
            $table->string('icon')->nullable();
            $table->boolean('guide_published')->default(false);
            $table->text('guide_intro')->nullable();
            $table->json('faqs')->nullable();
            $table->timestamps();
        });

        Schema::create('ingredient_substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('substitute_ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->string('substitute_name');
            $table->string('icon')->nullable();
            $table->json('purposes');
            $table->decimal('original_quantity', 10, 3);
            $table->string('original_unit', 12);
            // [{"name": "...", "qty": 1, "qty_max": null, "unit": "cup"}], per original_quantity of the original ingredient
            $table->json('components');
            $table->string('ratio_description');
            $table->text('advantages')->nullable();
            $table->text('limitations')->nullable();
            $table->text('impact')->nullable();
            $table->text('tip')->nullable();
            $table->json('dietary_tags');
            $table->json('allergens');
            $table->unsignedTinyInteger('quality_score')->default(50);
            $table->string('source')->nullable();
            $table->date('verified_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['ingredient_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_substitutes');
        Schema::dropIfExists('ingredients');
    }
};
