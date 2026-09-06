<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_page_id')->constrained('pages')->cascadeOnDelete();
            $table->text('to_url');
            $table->char('to_url_hash', 40);
            $table->string('anchor', 512)->nullable();
            $table->boolean('is_internal')->default(true);
            $table->boolean('nofollow')->default(false);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();

            $table->index(['crawl_id', 'is_internal']);
            $table->index(['project_id', 'to_url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_links');
    }
};
