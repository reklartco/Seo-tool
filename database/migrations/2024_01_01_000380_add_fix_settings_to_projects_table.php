<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('auto_apply_fixes')->default(false)->after('wp_connected_at');
            $table->unsignedInteger('daily_fix_limit')->default(0)->after('auto_apply_fixes');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['auto_apply_fixes', 'daily_fix_limit']);
        });
    }
};
