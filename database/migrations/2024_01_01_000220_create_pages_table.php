<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->char('url_hash', 40);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('content_type')->nullable();
            $table->string('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('h1')->nullable();
            $table->text('canonical')->nullable();
            $table->string('robots_meta')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('load_time_ms')->nullable();
            $table->unsignedInteger('internal_links_in')->default(0);
            $table->unsignedInteger('internal_links_out')->default(0);
            $table->unsignedTinyInteger('depth')->default(0);
            $table->char('content_hash', 40)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->foreignId('last_crawl_id')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
