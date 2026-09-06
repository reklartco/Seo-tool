<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gsc_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();   // encrypted cast
            $table->text('refresh_token')->nullable();  // encrypted cast
            $table->timestamp('expires_at')->nullable();
            $table->string('property')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_tokens');
    }
};
