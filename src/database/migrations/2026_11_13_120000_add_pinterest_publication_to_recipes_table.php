<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->boolean('posted_pinterest')->default(false)->after('posted_facebook');
            $table->timestamp('pinterest_posted_at')->nullable()->after('posted_pinterest');
            $table->string('pinterest_pin_id')->nullable()->after('pinterest_posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['posted_pinterest', 'pinterest_posted_at', 'pinterest_pin_id']);
        });
    }
};
