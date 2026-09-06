<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crawl_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_key');
            $table->string('severity'); // critical|warning|notice
            $table->string('category');  // meta|content|technical|links|images|schema
            $table->text('message');
            $table->json('details')->nullable();
            $table->string('status')->default('open'); // open|fixed|ignored|auto_fixed
            $table->string('resolved_by')->nullable(); // manual|ai|crawl
            $table->timestamp('fixed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status', 'severity']);
            $table->index(['project_id', 'rule_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
