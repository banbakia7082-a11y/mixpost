<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixpost_affiliate_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('network')->default('Amazon');
            $table->text('affiliate_url');
            $table->text('actual_scene');
            $table->text('benefit');
            $table->text('drawback');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('mixpost_affiliate_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affiliate_product_id')->constrained('mixpost_affiliate_products')->cascadeOnDelete();
            $table->text('parent_post');
            $table->text('reply_post');
            $table->string('angle');
            $table->json('scores');
            $table->json('evidence_post_ids')->nullable();
            $table->text('generation_reason')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixpost_affiliate_drafts');
        Schema::dropIfExists('mixpost_affiliate_products');
    }
};

