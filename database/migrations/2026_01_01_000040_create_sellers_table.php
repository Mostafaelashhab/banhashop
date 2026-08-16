<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 140);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();

            $table->string('status', 20)->default('pending');
            $table->boolean('is_verified')->default(false);
            // Early sellers are onboarded by hand; the admin owns this flag.
            $table->boolean('onboarded_by_admin')->default(false);

            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();

            // Denormalised, recomputed from real data only.
            $table->unsignedInteger('active_offers_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('accepted_orders_count')->default(0);
            $table->unsignedInteger('cancelled_orders_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'active_offers_count']);
        });

        Schema::create('seller_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 80)->nullable();
            $table->string('address_line', 220);
            $table->string('landmark', 160)->nullable();
            // Reserved for a later distance-based pricing model.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['seller_id', 'is_primary']);
        });

        // Which zones a seller is willing to deliver to at all.
        Schema::create('seller_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['seller_id', 'shipping_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_zone');
        Schema::dropIfExists('seller_locations');
        Schema::dropIfExists('sellers');
    }
};
