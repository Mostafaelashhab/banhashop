<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The customer places ONE order. Fulfilment is split per seller from day
     * one, so a multi-seller cart needs no schema rewrite later:
     *
     *   Order (what the customer agreed to pay)
     *     └── SellerOrder (what one store must fulfil)
     *           ├── OrderItem
     *           └── Shipment
     *   Payment (how the money moves — COD today)
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 160)->nullable();

            // Address snapshot — the order must survive the address being edited.
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_zone_name', 120);
            $table->string('shipping_street', 180);
            $table->string('shipping_building', 60)->nullable();
            $table->string('shipping_floor', 20)->nullable();
            $table->string('shipping_apartment', 20)->nullable();
            $table->string('shipping_landmark', 160)->nullable();
            $table->string('shipping_notes', 255)->nullable();

            $table->unsignedBigInteger('items_total_cents');
            $table->unsignedBigInteger('shipping_total_cents');
            $table->unsignedBigInteger('grand_total_cents');

            $table->string('status', 20)->default('placed');
            $table->string('payment_method', 20)->default('cod');
            $table->string('payment_status', 20)->default('pending');

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('seller_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->restrictOnDelete();
            $table->string('reference', 24)->unique();

            $table->unsignedBigInteger('items_total_cents');
            $table->unsignedBigInteger('shipping_cents');
            $table->unsignedBigInteger('total_cents');

            // Shipping decision snapshot, taken at checkout.
            $table->foreignId('shipping_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_rate_id')->nullable()->constrained('shipping_rates')->nullOnDelete();
            $table->string('shipping_provider_name', 120)->nullable();
            $table->unsignedSmallInteger('eta_min_hours')->nullable();
            $table->unsignedSmallInteger('eta_max_hours')->nullable();
            $table->timestamp('promised_at')->nullable();

            $table->string('status', 20)->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status', 'created_at'], 'seller_orders_dashboard_index');
            $table->index('order_id');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_offer_id')->nullable()->constrained()->nullOnDelete();

            // Product snapshot — catalog edits must not rewrite history.
            $table->string('product_name', 200);
            $table->string('product_slug', 220);
            $table->string('variant_label', 120)->nullable();
            $table->string('image_path')->nullable();
            $table->string('condition', 20)->default('new');

            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total_cents');
            $table->timestamps();

            $table->index('seller_order_id');
            $table->index('product_id');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number', 80)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('seller_order_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Abstraction point for Instapay / cards later. COD for now.
            $table->string('method', 20)->default('cod');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('amount_cents');
            $table->string('reference', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30);
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('seller_orders');
        Schema::dropIfExists('orders');
    }
};
