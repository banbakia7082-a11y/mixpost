<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixpost_threads_research_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('topic');
            $table->json('keywords');
            $table->unsignedTinyInteger('posts_per_account')->default(10);
            $table->unsignedTinyInteger('candidate_limit')->default(5);
            $table->string('frequency', 32)->default('weekly');
            $table->string('status', 32)->default('queued')->index();
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixpost_threads_research_requests');
    }
};
