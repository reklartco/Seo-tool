<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serp_top10', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->date('checked_at');
            $table->unsignedTinyInteger('position');
            $table->string('domain');
            $table->text('url');
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index(['keyword_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serp_top10');
    }
};
