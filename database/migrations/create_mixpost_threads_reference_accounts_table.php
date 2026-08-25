<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixpost_threads_reference_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('handle')->unique();
            $table->string('display_name')->nullable();
            $table->string('profile_url', 500);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('mixpost_affiliate_posts', function (Blueprint $table) {
            $table->foreignId('threads_reference_account_id')
                ->nullable()
                ->after('id')
                ->constrained('mixpost_threads_reference_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mixpost_affiliate_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('threads_reference_account_id');
        });

        Schema::dropIfExists('mixpost_threads_reference_accounts');
    }
};
