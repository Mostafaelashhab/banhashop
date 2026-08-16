<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shipping is a first-class subsystem, never inlined into the order.
     *
     *   ShippingProvider -> ShippingRate (per zone, optionally per seller)
     *   Seller enables providers + declares the zones it serves
     *   Shipment records what actually happened
     *
     * A future in-house "Banha.shop Delivery" is just another provider row.
     */
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('logo_path')->nullable();
            // platform | third_party | seller (the store delivers itself)
            $table->string('type', 20)->default('third_party');
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            // NULL = the provider's platform-wide rate for this zone.
            // Set   = this seller's own rate (self-delivery or negotiated).
            $table->foreignId('seller_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('price_cents');
            $table->unsignedBigInteger('free_over_cents')->nullable();
            $table->unsignedSmallInteger('eta_min_hours')->default(24);
            $table->unsignedSmallInteger('eta_max_hours')->default(48);
            // Orders placed after this local time slip to the next day.
            $table->time('same_day_cutoff')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['shipping_provider_id', 'shipping_zone_id', 'seller_id'],
                'shipping_rates_unique_scope'
            );
            $table->index(['shipping_zone_id', 'is_active', 'price_cents'], 'shipping_rates_zone_lookup');
        });

        Schema::create('seller_shipping_provider', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['seller_id', 'shipping_provider_id'], 'seller_shipping_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_shipping_provider');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_providers');
    }
};
