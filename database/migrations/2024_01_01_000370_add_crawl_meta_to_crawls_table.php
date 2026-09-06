<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crawls', function (Blueprint $table) {
            $table->unsignedInteger('new_issues')->default(0)->after('issues_count');
            $table->unsignedInteger('resolved_issues')->default(0)->after('new_issues');
            $table->json('robots')->nullable()->after('resolved_issues');
        });
    }

    public function down(): void
    {
        Schema::table('crawls', function (Blueprint $table) {
            $table->dropColumn(['new_issues', 'resolved_issues', 'robots']);
        });
    }
};
