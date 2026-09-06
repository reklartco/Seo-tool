<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('source'); // wp|gsc|dataforseo|pagespeed
            $table->string('action');
            $table->json('payload')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations_log');
    }
};
