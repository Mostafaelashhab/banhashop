<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A failed search is demand data, not a dead end. Requests are grouped by
     * a normalised key so admins can see "AirPods Pro — 17 requests in Banha"
     * and take that list to local stores.
     */
    public function up(): void
    {
        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('query_text', 180);
            $table->string('normalized_key', 180);
            $table->string('note', 255)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['normalized_key', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Seller-submitted catalog additions live in the products table with a
        // `pending` status; this table only records customer demand.
    }

    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
