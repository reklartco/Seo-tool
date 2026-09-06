<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->unsignedInteger('crawled_pages')->default(0);
            $table->unsignedInteger('serp_queries')->default(0);
            $table->unsignedInteger('ai_generations')->default(0);
            $table->unsignedInteger('ai_tokens')->default(0);
            $table->unsignedInteger('applied_fixes')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_usage');
    }
};
