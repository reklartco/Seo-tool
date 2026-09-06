<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_monthly')->default(0);
            $table->unsignedInteger('price_yearly')->default(0);
            $table->string('currency', 3)->default('TRY');

            // Limit columns (spec §3 / §9)
            $table->unsignedInteger('max_projects')->default(1);
            $table->unsignedInteger('max_keywords')->default(10);
            $table->unsignedInteger('max_monthly_crawl_pages')->default(1000);
            $table->unsignedInteger('max_auto_fix_sites')->default(0);
            $table->unsignedInteger('daily_fix_page_limit')->default(0);
            $table->unsignedInteger('max_monthly_ai_generations')->default(0);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
