<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gsc_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('query');
            $table->text('page')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 6, 4)->default(0);
            $table->decimal('position', 6, 2)->default(0);
            $table->timestamps();

            $table->index(['project_id', 'date']);
            $table->index(['project_id', 'query']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_queries');
    }
};
