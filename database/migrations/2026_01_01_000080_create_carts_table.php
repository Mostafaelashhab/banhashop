<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Guests get a cart too; it is merged on login.
            $table->string('session_token', 64)->nullable()->unique();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            // Snapshot at add-to-cart time so a price change is detectable.
            $table->unsignedBigInteger('unit_price_cents');
            $table->timestamps();

            $table->unique(['cart_id', 'seller_offer_id']);
            $table->index(['cart_id', 'seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
