<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooking_guide_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('name_fr', 100)->nullable();
            $table->string('icon', 50)->default('fa-book-open');
            $table->text('description');
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cooking_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('cooking_guide_categories')->cascadeOnDelete();
            $table->string('slug', 150)->unique();
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->text('excerpt');
            $table->text('quick_answer');
            $table->json('what_you_need')->nullable(); // array of items/equipment
            $table->json('content_sections'); // array of ['title' => ..., 'body' => ...]
            $table->json('common_mistakes')->nullable(); // array of ['mistake' => ..., 'fix' => ...]
            $table->json('pro_tips')->nullable(); // array of tips
            $table->json('faqs')->nullable(); // array of ['question' => ..., 'answer' => ...]
            $table->json('sources')->nullable(); // array of ['title' => ..., 'url' => ..., 'authority' => ...]
            $table->json('problem_keywords')->nullable(); // e.g. ["my chicken is dry", "dry meat", "rubbery chicken"]
            $table->unsignedSmallInteger('reading_time_minutes')->default(4);
            $table->string('image_path')->nullable();
            $table->string('author_name', 100)->default('TopicTrail Kitchen Team');
            $table->boolean('featured')->default(false)->index();
            $table->string('related_tool_key', 50)->nullable(); // converter, substitution, nutrition, meal-planner, what-can-i-cook
            $table->string('related_tool_headline')->nullable();
            $table->text('related_tool_text')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_published']);
        });

        Schema::create('cooking_guide_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooking_guide_id')->constrained('cooking_guides')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cooking_guide_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooking_guide_recipes');
        Schema::dropIfExists('cooking_guides');
        Schema::dropIfExists('cooking_guide_categories');
    }
};
