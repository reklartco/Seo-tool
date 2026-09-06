<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedInteger('html_size')->nullable()->after('word_count');
            $table->unsignedTinyInteger('redirect_hops')->default(0)->after('status_code');
            $table->text('final_url')->nullable()->after('url');
            $table->boolean('in_sitemap')->default(false)->after('depth');
            $table->boolean('indexable')->default(true)->after('in_sitemap');
            $table->unsignedInteger('issues_count')->default(0)->after('indexable');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['html_size', 'redirect_hops', 'final_url', 'in_sitemap', 'indexable', 'issues_count']);
        });
    }
};
