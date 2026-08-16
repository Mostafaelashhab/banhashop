<?php

namespace Database\Seeders;

use App\Models\ProductRequest;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demand the catalog cannot serve yet. This is the list an admin takes to
 * local stores — "17 people in Banha asked for AirPods Pro this month".
 */
class ProductRequestSeeder extends Seeder
{
    public function run(): void
    {
        $zones = ShippingZone::pluck('id', 'slug');

        $requests = [
            ['AirPods Pro 2', 'banha-center', 6],
            ['AirPods Pro 2', 'new-banha', 4],
            ['AirPods Pro 2', 'kafr-elgazzar', 2],
            ['بلايستيشن 5 سليم', 'banha-center', 5],
            ['بلايستيشن 5 سليم', 'new-banha', 3],
            ['ديب فريزر كريازي 5 درج', 'kafr-elgazzar', 3],
            ['مكيف شارب 1.5 حصان', 'banha-center', 4],
            ['مكيف شارب 1.5 حصان', 'greater-banha', 2],
            ['ايباد اير الجيل الحادي عشر', 'banha-center', 2],
        ];

        foreach ($requests as [$text, $zoneSlug, $count]) {
            for ($i = 0; $i < $count; $i++) {
                ProductRequest::create([
                    'shipping_zone_id' => $zones[$zoneSlug] ?? null,
                    'query_text' => $text,
                    'status' => ProductRequest::STATUS_OPEN,
                    'created_at' => Carbon::now()->subDays(random_int(1, 25)),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
