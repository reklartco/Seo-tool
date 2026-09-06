<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->text('target_url')->nullable();
            $table->unsignedInteger('search_volume')->nullable();
            $table->decimal('cpc', 8, 2)->nullable();
            $table->decimal('competition', 4, 3)->nullable();
            $table->string('tag')->nullable();
            $table->unsignedSmallInteger('current_rank')->nullable();
            $table->unsignedSmallInteger('previous_rank')->nullable();
            $table->unsignedSmallInteger('best_rank')->nullable();
            $table->unsignedSmallInteger('start_rank')->nullable();
            $table->smallInteger('rank_delta')->default(0);
            $table->text('serp_url')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
