<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mixpost_affiliate_posts', function (Blueprint $table) {
            $table->json('content_analysis')->nullable()->after('content');
            $table->dateTime('analyzed_at')->nullable()->after('content_analysis');
            $table->string('analysis_version')->nullable()->after('analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('mixpost_affiliate_posts', fn (Blueprint $table) =>
            $table->dropColumn(['content_analysis', 'analyzed_at', 'analysis_version'])
        );
    }
};

