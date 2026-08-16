<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The central catalog. Every product here exists exactly once — sellers attach
 * offers to these rows rather than creating their own duplicates.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->categories();
        $brands = $this->brands();

        foreach ($this->products() as $index => $data) {
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $categories[$data['category']],
                    'brand_id' => $brands[$data['brand']] ?? null,
                    'name' => $data['name'],
                    'variant_label' => $data['variant'] ?? null,
                    'model' => $data['model'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'description' => $data['description'] ?? null,
                    'highlights' => $data['highlights'] ?? null,
                    'search_keywords' => $data['keywords'] ?? null,
                    'status' => ProductStatus::Published,
                    'meta_title' => ($data['name'].' '.($data['variant'] ?? '')).' — سعره في بنها',
                    'meta_description' => 'قارن أسعار '.$data['name'].' بين متاجر بنها واعرف السعر النهائي شامل التوصيل قبل ما تطلب.',
                    'published_at' => Carbon::now()->subDays(40 - $index),
                ]
            );

            foreach ($data['attributes'] ?? [] as $position => [$name, $value]) {
                $product->attributes()->updateOrCreate(
                    ['name' => $name],
                    ['value' => $value, 'position' => $position]
                );
            }
        }

        $this->linkVariantGroups();
    }

    /** @return array<string, int> slug => id */
    private function categories(): array
    {
        $tree = [
            ['موبايلات وتابلت', 'mobile-phones', null, [
                ['موبايلات', 'smartphones'],
                ['تابلت', 'tablets'],
                ['إكسسوارات موبايل', 'mobile-accessories'],
            ]],
            ['أجهزة منزلية', 'home-appliances', null, [
                ['غسالات', 'washing-machines'],
                ['ثلاجات', 'refrigerators'],
                ['أجهزة مطبخ صغيرة', 'small-kitchen-appliances'],
            ]],
            ['إلكترونيات', 'electronics', null, [
                ['شاشات وتلفزيونات', 'televisions'],
                ['سماعات وصوتيات', 'audio'],
            ]],
            ['كمبيوتر ولابتوب', 'computers', null, [
                ['لابتوب', 'laptops'],
            ]],
        ];

        $ids = [];
        $position = 1;

        foreach ($tree as [$name, $slug, $parent, $children]) {
            $root = Category::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'parent_id' => null,
                'position' => $position++,
                'is_active' => true,
                'meta_title' => $name.' في بنها — قارن أسعار المتاجر المحلية',
                'meta_description' => 'كل عروض '.$name.' من متاجر بنها مع السعر النهائي شامل التوصيل.',
            ]);

            $ids[$slug] = $root->id;
            $childPosition = 1;

            foreach ($children as [$childName, $childSlug]) {
                $child = Category::updateOrCreate(['slug' => $childSlug], [
                    'name' => $childName,
                    'parent_id' => $root->id,
                    'position' => $childPosition++,
                    'is_active' => true,
                    'meta_title' => $childName.' في بنها — أسعار المتاجر المحلية',
                ]);

                $ids[$childSlug] = $child->id;
            }
        }

        return $ids;
    }

    /** @return array<string, int> slug => id */
    private function brands(): array
    {
        // The Arabic alias is not decoration: customers type "سامسونج", and
        // without it the whole brand is unfindable in its own market.
        $brands = [
            'apple' => ['Apple', 'ابل ايفون'],
            'samsung' => ['Samsung', 'سامسونج'],
            'xiaomi' => ['Xiaomi', 'شاومي ريدمي'],
            'oppo' => ['Oppo', 'اوبو'],
            'realme' => ['Realme', 'ريلمي'],
            'infinix' => ['Infinix', 'انفينكس'],
            'lg' => ['LG', 'ال جي'],
            'toshiba' => ['Toshiba', 'توشيبا'],
            'sharp' => ['Sharp', 'شارب'],
            'fresh' => ['Fresh', 'فريش'],
            'kiriazi' => ['Kiriazi', 'كريازي'],
            'braun' => ['Braun', 'براون'],
            'tornado' => ['Tornado', 'تورنيدو'],
            'anker' => ['Anker', 'انكر'],
            'hp' => ['HP', 'اتش بي'],
        ];

        $ids = [];

        foreach ($brands as $slug => [$name, $nameAr]) {
            $ids[$slug] = Brand::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'name_ar' => $nameAr,
                'is_active' => true,
            ])->id;
        }

        return $ids;
    }

    /**
     * A deliberately small, believable catalog. Real model names, real specs,
     * no filler rows.
     */
    private function products(): array
    {
        return [
            [
                'name' => 'iPhone 17 Pro', 'variant' => '256 جيجا', 'slug' => 'iphone-17-pro-256gb',
                'keywords' => 'ايفون 17 برو موبايل ابل',
                'category' => 'smartphones', 'brand' => 'apple', 'model' => 'A3210', 'barcode' => '194253000011',
                'description' => 'موبايل أبل الرئيسي بشاشة 6.3 بوصة وكاميرا ثلاثية وشريحة A19 Pro.',
                'highlights' => ['شاشة 6.3 بوصة ProMotion', 'شريحة A19 Pro', 'كاميرا ثلاثية 48 ميجابكسل', 'شحن USB-C'],
                'attributes' => [['الشاشة', '6.3 بوصة OLED'], ['المعالج', 'Apple A19 Pro'], ['السعة', '256 جيجا'], ['البطارية', '3600 مللي أمبير'], ['الشبكة', '5G']],
            ],
            [
                'name' => 'iPhone 17 Pro', 'variant' => '512 جيجا', 'slug' => 'iphone-17-pro-512gb',
                'keywords' => 'ايفون 17 برو موبايل ابل',
                'category' => 'smartphones', 'brand' => 'apple', 'model' => 'A3210', 'barcode' => '194253000028',
                'description' => 'نفس الموديل بسعة تخزين مضاعفة.',
                'highlights' => ['شاشة 6.3 بوصة ProMotion', 'شريحة A19 Pro', 'سعة 512 جيجا'],
                'attributes' => [['الشاشة', '6.3 بوصة OLED'], ['المعالج', 'Apple A19 Pro'], ['السعة', '512 جيجا'], ['الشبكة', '5G']],
            ],
            [
                'name' => 'Samsung Galaxy S25', 'variant' => '256 جيجا', 'slug' => 'samsung-galaxy-s25-256gb',
                'keywords' => 'سامسونج جالاكسي اس 25 موبايل',
                'category' => 'smartphones', 'brand' => 'samsung', 'model' => 'SM-S931B', 'barcode' => '880609800012',
                'description' => 'موبايل سامسونج الرئيسي بشاشة Dynamic AMOLED وكاميرا 200 ميجابكسل.',
                'highlights' => ['شاشة 6.2 بوصة 120Hz', 'كاميرا 200 ميجابكسل', 'شحن سريع 45 وات'],
                'attributes' => [['الشاشة', '6.2 بوصة AMOLED'], ['الرام', '12 جيجا'], ['السعة', '256 جيجا'], ['البطارية', '4000 مللي أمبير']],
            ],
            [
                'name' => 'Xiaomi Redmi Note 14 Pro', 'variant' => '256 جيجا', 'slug' => 'redmi-note-14-pro-256gb',
                'keywords' => 'شاومي ريدمي نوت 14 برو موبايل',
                'category' => 'smartphones', 'brand' => 'xiaomi', 'model' => '2409BRN2CY',
                'description' => 'الفئة المتوسطة الأكثر مبيعًا بكاميرا 200 ميجابكسل وشاشة AMOLED.',
                'highlights' => ['شاشة 6.67 بوصة AMOLED', 'كاميرا 200 ميجابكسل', 'شحن 45 وات', 'بطارية 5500 مللي أمبير'],
                'attributes' => [['الشاشة', '6.67 بوصة AMOLED'], ['الرام', '8 جيجا'], ['السعة', '256 جيجا'], ['البطارية', '5500 مللي أمبير']],
            ],
            [
                'name' => 'Oppo Reno 13', 'variant' => '256 جيجا', 'slug' => 'oppo-reno-13-256gb',
                'keywords' => 'اوبو رينو 13 موبايل',
                'category' => 'smartphones', 'brand' => 'oppo', 'model' => 'CPH2645',
                'description' => 'تصميم نحيف وكاميرا أمامية عالية الدقة.',
                'highlights' => ['شاشة 6.6 بوصة', 'كاميرا أمامية 32 ميجابكسل', 'شحن 80 وات'],
                'attributes' => [['الشاشة', '6.6 بوصة AMOLED'], ['الرام', '8 جيجا'], ['السعة', '256 جيجا']],
            ],
            [
                'name' => 'Infinix Hot 50', 'variant' => '128 جيجا', 'slug' => 'infinix-hot-50-128gb',
                'keywords' => 'انفينكس هوت 50 موبايل',
                'category' => 'smartphones', 'brand' => 'infinix', 'model' => 'X6720',
                'description' => 'موبايل اقتصادي ببطارية كبيرة وشاشة 120 هرتز.',
                'highlights' => ['شاشة 6.7 بوصة 120Hz', 'بطارية 5000 مللي أمبير', 'سعر اقتصادي'],
                'attributes' => [['الشاشة', '6.7 بوصة'], ['الرام', '6 جيجا'], ['السعة', '128 جيجا']],
            ],
            [
                'name' => 'Realme Note 60', 'variant' => '128 جيجا', 'slug' => 'realme-note-60-128gb',
                'keywords' => 'ريلمي نوت 60 موبايل',
                'category' => 'smartphones', 'brand' => 'realme', 'model' => 'RMX3933',
                'description' => 'موبايل اقتصادي مقاوم للماء والغبار.',
                'highlights' => ['مقاومة IP54', 'بطارية 5000 مللي أمبير'],
                'attributes' => [['الشاشة', '6.74 بوصة'], ['الرام', '4 جيجا'], ['السعة', '128 جيجا']],
            ],
            [
                'name' => 'Samsung Galaxy Tab A9+', 'variant' => '128 جيجا واي فاي', 'slug' => 'galaxy-tab-a9-plus-128gb',
                'keywords' => 'سامسونج تابلت جالاكسي تاب',
                'category' => 'tablets', 'brand' => 'samsung', 'model' => 'SM-X210',
                'description' => 'تابلت 11 بوصة مناسب للدراسة والمشاهدة.',
                'highlights' => ['شاشة 11 بوصة 90Hz', 'سماعات رباعية', 'بطارية 7040 مللي أمبير'],
                'attributes' => [['الشاشة', '11 بوصة'], ['السعة', '128 جيجا'], ['الاتصال', 'واي فاي']],
            ],
            [
                'name' => 'Anker PowerCore 20000', 'variant' => '20000 مللي أمبير', 'slug' => 'anker-powercore-20000',
                'keywords' => 'انكر باور بانك بطارية متنقلة',
                'category' => 'mobile-accessories', 'brand' => 'anker', 'model' => 'A1287',
                'description' => 'باور بانك بمنفذين وشحن سريع.',
                'highlights' => ['سعة 20000 مللي أمبير', 'شحن سريع 22.5 وات', 'منفذ USB-C'],
                'attributes' => [['السعة', '20000 مللي أمبير'], ['المنافذ', 'USB-C + USB-A']],
            ],
            [
                'name' => 'شاحن Anker 65W GaN', 'variant' => 'ثلاثة منافذ', 'slug' => 'anker-65w-gan-charger',
                'keywords' => 'انكر شاحن سريع',
                'category' => 'mobile-accessories', 'brand' => 'anker', 'model' => 'A2668',
                'description' => 'شاحن صغير الحجم يكفي اللابتوب والموبايل معًا.',
                'highlights' => ['قدرة 65 وات', 'ثلاثة منافذ', 'حجم صغير'],
                'attributes' => [['القدرة', '65 وات'], ['المنافذ', '2×USB-C + USB-A']],
            ],
            [
                'name' => 'غسالة LG أوتوماتيك', 'variant' => '8 كيلو', 'slug' => 'lg-washing-machine-8kg',
                'keywords' => 'غسالة ال جي فول اوتوماتيك',
                'category' => 'washing-machines', 'brand' => 'lg', 'model' => 'F2V3HYP6W',
                'description' => 'غسالة فول أوتوماتيك بمحرك انفرتر وضمان طويل.',
                'highlights' => ['سعة 8 كيلو', 'محرك Inverter Direct Drive', 'بخار', '1200 لفة'],
                'attributes' => [['السعة', '8 كيلو'], ['سرعة العصر', '1200 لفة/دقيقة'], ['المحرك', 'Inverter Direct Drive']],
            ],
            [
                'name' => 'غسالة Toshiba أوتوماتيك', 'variant' => '7 كيلو', 'slug' => 'toshiba-washing-machine-7kg',
                'keywords' => 'غسالة توشيبا فول اوتوماتيك',
                'category' => 'washing-machines', 'brand' => 'toshiba', 'model' => 'TW-BJ80S2',
                'description' => 'غسالة اقتصادية بسعة مناسبة للأسرة الصغيرة.',
                'highlights' => ['سعة 7 كيلو', '15 برنامج غسيل'],
                'attributes' => [['السعة', '7 كيلو'], ['سرعة العصر', '1000 لفة/دقيقة']],
            ],
            [
                'name' => 'ثلاجة Kiriazi نوفروست', 'variant' => '16 قدم', 'slug' => 'kiriazi-fridge-16ft',
                'keywords' => 'ثلاجة كريازي نوفروست',
                'category' => 'refrigerators', 'brand' => 'kiriazi', 'model' => 'KH436',
                'description' => 'ثلاجة نوفروست بابين بسعة مناسبة للعائلة.',
                'highlights' => ['سعة 16 قدم', 'نوفروست', 'ضمان 5 سنوات على الموتور'],
                'attributes' => [['السعة', '16 قدم'], ['النوع', 'نوفروست بابين']],
            ],
            [
                'name' => 'ثلاجة Fresh نوفروست', 'variant' => '14 قدم', 'slug' => 'fresh-fridge-14ft',
                'keywords' => 'ثلاجة فريش نوفروست',
                'category' => 'refrigerators', 'brand' => 'fresh', 'model' => 'FNT-B400KT',
                'description' => 'ثلاجة اقتصادية بسعة 14 قدم.',
                'highlights' => ['سعة 14 قدم', 'نوفروست'],
                'attributes' => [['السعة', '14 قدم'], ['النوع', 'نوفروست بابين']],
            ],
            [
                'name' => 'خلاط Braun', 'variant' => '800 وات', 'slug' => 'braun-blender-800w',
                'keywords' => 'خلاط براون مطبخ',
                'category' => 'small-kitchen-appliances', 'brand' => 'braun', 'model' => 'JB3060',
                'description' => 'خلاط زجاجي بقدرة 800 وات.',
                'highlights' => ['قدرة 800 وات', 'دورق زجاجي 1.75 لتر'],
                'attributes' => [['القدرة', '800 وات'], ['السعة', '1.75 لتر']],
            ],
            [
                'name' => 'مروحة Tornado عمود', 'variant' => '16 بوصة', 'slug' => 'tornado-stand-fan-16',
                'keywords' => 'مروحة تورنيدو عمود',
                'category' => 'small-kitchen-appliances', 'brand' => 'tornado', 'model' => 'TSF-16',
                'description' => 'مروحة عمود بثلاث سرعات.',
                'highlights' => ['16 بوصة', '3 سرعات', 'ريموت'],
                'attributes' => [['المقاس', '16 بوصة'], ['السرعات', '3']],
            ],
            [
                'name' => 'شاشة Samsung Crystal UHD', 'variant' => '55 بوصة', 'slug' => 'samsung-crystal-uhd-55',
                'keywords' => 'شاشة سامسونج تلفزيون سمارت',
                'category' => 'televisions', 'brand' => 'samsung', 'model' => 'UA55DU7000',
                'description' => 'شاشة ذكية 4K بنظام Tizen.',
                'highlights' => ['دقة 4K', 'نظام Tizen', '3 مداخل HDMI'],
                'attributes' => [['المقاس', '55 بوصة'], ['الدقة', '4K UHD'], ['النظام', 'Tizen']],
            ],
            [
                'name' => 'شاشة Sharp 4K', 'variant' => '50 بوصة', 'slug' => 'sharp-4k-50',
                'keywords' => 'شاشة شارب تلفزيون سمارت',
                'category' => 'televisions', 'brand' => 'sharp', 'model' => '4T-C50FK',
                'description' => 'شاشة ذكية 50 بوصة بدقة 4K.',
                'highlights' => ['دقة 4K', 'أندرويد', 'صوت 20 وات'],
                'attributes' => [['المقاس', '50 بوصة'], ['الدقة', '4K UHD']],
            ],
            [
                'name' => 'سماعة Samsung Galaxy Buds3', 'variant' => 'أبيض', 'slug' => 'galaxy-buds3-white',
                'keywords' => 'سماعة سامسونج بلوتوث ايربودز',
                'category' => 'audio', 'brand' => 'samsung', 'model' => 'SM-R530',
                'description' => 'سماعة لاسلكية بعزل ضوضاء نشط.',
                'highlights' => ['عزل ضوضاء نشط', 'بلوتوث 5.4', 'مقاومة IP57'],
                'attributes' => [['النوع', 'لاسلكية داخل الأذن'], ['البطارية', 'حتى 30 ساعة مع العلبة']],
            ],
            [
                'name' => 'لابتوب HP 15', 'variant' => 'Core i5 / 16 جيجا', 'slug' => 'hp-15-i5-16gb',
                'keywords' => 'لابتوب اتش بي كمبيوتر محمول',
                'category' => 'laptops', 'brand' => 'hp', 'model' => '15-fd0xxx',
                'description' => 'لابتوب للاستخدام اليومي والدراسة.',
                'highlights' => ['معالج Core i5 الجيل 13', 'رام 16 جيجا', 'SSD 512 جيجا', 'شاشة 15.6 بوصة'],
                'attributes' => [['المعالج', 'Intel Core i5-1335U'], ['الرام', '16 جيجا'], ['التخزين', '512 جيجا SSD'], ['الشاشة', '15.6 بوصة FHD']],
            ],
        ];
    }

    /**
     * Variants are sibling products grouped under one parent, so an offer
     * always points at exactly one buyable thing.
     */
    private function linkVariantGroups(): void
    {
        $groups = [
            'iphone-17-pro-256gb' => ['iphone-17-pro-512gb'],
        ];

        foreach ($groups as $parentSlug => $childSlugs) {
            $parent = Product::where('slug', $parentSlug)->first();

            if ($parent === null) {
                continue;
            }

            $parent->update(['parent_id' => $parent->id]);

            Product::whereIn('slug', $childSlugs)->update(['parent_id' => $parent->id]);
        }
    }
}
