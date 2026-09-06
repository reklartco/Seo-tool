<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('domain');
            $table->string('protocol', 8)->default('https');
            $table->string('language', 5)->default('tr');
            $table->string('country', 2)->default('TR');
            $table->unsignedInteger('search_engine_location_code')->default(2792); // Turkey
            $table->string('cms')->default('custom'); // wordpress|woocommerce|custom
            $table->string('crawl_frequency')->default('weekly'); // daily|weekly|manual
            $table->unsignedInteger('max_pages')->default(500);
            $table->unsignedTinyInteger('health_score')->nullable();
            $table->smallInteger('health_score_delta')->default(0);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamp('gsc_connected_at')->nullable();
            $table->timestamp('wp_connected_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
