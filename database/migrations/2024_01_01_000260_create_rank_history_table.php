<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rank_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->date('checked_at');
            $table->unsignedSmallInteger('rank')->nullable(); // null = not in top 100
            $table->text('serp_url')->nullable();
            $table->json('serp_features')->nullable();
            $table->timestamps();

            $table->unique(['keyword_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_history');
    }
};
