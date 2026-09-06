<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('field'); // title|meta_description|alt|...
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('generated_by')->default('ai'); // ai|user
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->string('status')->default('draft'); // draft|approved|applied|failed|rolled_back
            $table->string('wp_object_type')->nullable();
            $table->unsignedBigInteger('wp_object_id')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixes');
    }
};
