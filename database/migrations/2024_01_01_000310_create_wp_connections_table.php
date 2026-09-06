<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('site_url');
            $table->string('api_key_hash');
            $table->string('plugin_version')->nullable();
            $table->string('wp_version')->nullable();
            $table->string('seo_plugin')->nullable(); // yoast|rankmath|none
            $table->json('capabilities')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_connections');
    }
};
