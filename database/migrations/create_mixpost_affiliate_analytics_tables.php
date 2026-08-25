<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixpost_affiliate_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('platform', 32)->index();
            $table->string('external_post_id')->nullable();
            $table->string('post_url', 500);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_category')->nullable();
            $table->string('affiliate_network')->nullable();
            $table->text('affiliate_url')->nullable();
            $table->string('post_type')->nullable();
            $table->string('image_type')->nullable();
            $table->boolean('disclosure_present')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'post_url'], 'affiliate_posts_platform_url_unique');
        });

        Schema::create('mixpost_affiliate_post_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_post_id')
                ->constrained('mixpost_affiliate_posts')
                ->cascadeOnDelete();
            $table->dateTime('captured_at');
            $table->unsignedBigInteger('views')->nullable();
            $table->unsignedBigInteger('reactions')->nullable();
            $table->unsignedBigInteger('replies')->nullable();
            $table->unsignedBigInteger('reposts')->nullable();
            $table->unsignedBigInteger('quotes')->nullable();
            $table->unsignedBigInteger('link_clicks')->nullable();
            $table->unsignedBigInteger('sales')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->json('raw_metrics')->nullable();
            $table->timestamps();

            $table->unique(['affiliate_post_id', 'captured_at'], 'affiliate_snapshots_post_captured_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixpost_affiliate_post_snapshots');
        Schema::dropIfExists('mixpost_affiliate_posts');
    }
};
