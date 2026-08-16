<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a catalog photograph came from, when the licence requires saying so.
 *
 * Own photography and CC0 sources leave this null. Anything under CC BY or
 * CC BY-SA legally must carry attribution, and a bulk importer that had
 * nowhere to record it would be a tool for quietly breaking licences.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('credit', 180)->nullable()->after('alt');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('credit');
        });
    }
};
