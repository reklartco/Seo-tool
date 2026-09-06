<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crawl_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('h1')->nullable();
            $table->text('canonical')->nullable();
            $table->string('robots_meta')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->char('content_hash', 40)->nullable();
            $table->timestamps();

            $table->index(['page_id', 'crawl_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_snapshots');
    }
};
