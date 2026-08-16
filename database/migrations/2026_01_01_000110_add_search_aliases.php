<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Egyptian customers type "سامسونج" and "ايفون", not "Samsung" and
     * "iPhone". Without aliases the catalog is invisible to half its audience,
     * so both the brand and the product can carry extra search terms that are
     * folded into the FULLTEXT haystack.
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('name_ar', 120)->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('search_keywords', 255)->nullable()->after('search_text');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('search_keywords');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
    }
};
