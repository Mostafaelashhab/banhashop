<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('price_cents');
            $table->unsignedBigInteger('compare_at_price_cents')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('sku', 80)->nullable();
            $table->string('condition', 20)->default('new');
            $table->string('status', 20)->default('active');
            $table->string('note', 200)->nullable();

            // Inventory trust: the customer is told how fresh this number is.
            $table->timestamp('inventory_updated_at')->nullable();
            $table->timestamp('price_updated_at')->nullable();
            $table->timestamps();

            // One offer per seller per product per condition: a store can list
            // the same phone as both new and refurbished, but not twice as new.
            $table->unique(['product_id', 'seller_id', 'condition'], 'seller_offers_unique_listing');
            $table->index(['product_id', 'status', 'price_cents'], 'seller_offers_comparison_index');
            $table->index(['seller_id', 'status']);
            $table->index('inventory_updated_at');
        });

        // Real history behind "stock updated 14 minutes ago" and, later, behind
        // any reliability score. Never invent trust metrics without this data.
        Schema::create('offer_inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 30);
            $table->unsignedBigInteger('price_cents_before')->nullable();
            $table->unsignedBigInteger('price_cents_after')->nullable();
            $table->unsignedInteger('stock_before')->nullable();
            $table->unsignedInteger('stock_after')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['seller_offer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_inventory_logs');
        Schema::dropIfExists('seller_offers');
    }
};
