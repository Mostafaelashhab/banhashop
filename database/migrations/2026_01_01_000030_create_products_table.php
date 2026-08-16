<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The central catalog. A product exists exactly once; sellers attach
     * offers to it. Variants (256GB / black) are modelled as sibling products
     * grouped by `parent_id` so an offer always points at one buyable thing.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            // e.g. "256 جيجا / تيتانيوم" — what separates this row from its siblings.
            $table->string('variant_label', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('mpn', 80)->nullable();
            $table->string('barcode', 32)->nullable();

            $table->text('description')->nullable();
            // Short bullet specs rendered on the product page.
            $table->json('highlights')->nullable();
            // Normalised (Arabic-folded) haystack used by the FULLTEXT index.
            $table->text('search_text')->nullable();

            $table->string('image_path')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();

            $table->string('status', 20)->default('draft');
            $table->string('rejection_reason', 255)->nullable();

            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();

            // Denormalised offer aggregates. Maintained by
            // App\Services\Catalog\ProductAggregateUpdater — never by Blade.
            $table->unsignedInteger('offers_count')->default(0);
            $table->unsignedBigInteger('min_price_cents')->nullable();
            $table->unsignedBigInteger('max_price_cents')->nullable();
            $table->unsignedInteger('sellers_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index(['status', 'brand_id']);
            $table->index(['status', 'offers_count']);
            $table->index(['status', 'min_price_cents']);
            $table->index('barcode');
            $table->index('parent_id', 'products_variant_group_index');
        });

        // MySQL FULLTEXT: enough for a single-city catalog. A dedicated search
        // engine is only justified once relevance or scale actually demands it.
        Schema::table('products', function (Blueprint $table) {
            $table->fullText('search_text', 'products_search_fulltext');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt', 180)->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });

        // Category-specific attributes without forcing every category into the
        // same rigid variant model.
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('value', 180);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
