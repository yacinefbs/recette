<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->boolean('posted_facebook')->default(false)->after('instructions');
            $table->timestamp('facebook_posted_at')->nullable()->after('posted_facebook');
            $table->string('facebook_post_id')->nullable()->after('facebook_posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['posted_facebook', 'facebook_posted_at', 'facebook_post_id']);
        });
    }
};
