<?php

namespace Database\Seeders;

use App\Enums\SellerStatus;
use App\Enums\UserRole;
use App\Models\Seller;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $zones = ShippingZone::pluck('id', 'slug');
        $storeDelivery = ShippingProvider::where('slug', 'store-delivery')->first();
        $fast = ShippingProvider::where('slug', 'sarie-banha')->first();
        $cheap = ShippingProvider::where('slug', 'waseet-express')->first();

        foreach ($this->stores() as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['owner'],
                    'phone' => $data['phone'],
                    'password' => 'password',
                    'role' => UserRole::Seller,
                    'is_active' => true,
                ]
            );

            $seller = Seller::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'phone' => $data['phone'],
                    'whatsapp' => $data['phone'],
                    'status' => SellerStatus::Active,
                    'is_verified' => $data['verified'],
                    // Early stores are onboarded by hand — that is the plan,
                    // not a shortcut.
                    'onboarded_by_admin' => true,
                    'meta_title' => $data['name'].' — عروض وأسعار في بنها',
                    'meta_description' => 'تصفح عروض '.$data['name'].' في بنها وقارن السعر النهائي شامل التوصيل.',
                ]
            );

            $seller->locations()->updateOrCreate(
                ['is_primary' => true],
                [
                    'shipping_zone_id' => $zones[$data['zone']],
                    'label' => 'الفرع الرئيسي',
                    'address_line' => $data['address'],
                    'is_primary' => true,
                ]
            );

            // Zones this store is willing to deliver to.
            $seller->zones()->sync(collect($data['serves'])->map(fn ($slug) => $zones[$slug])->all());

            // Couriers this store works with.
            $providerIds = [];
            foreach ($data['providers'] as $slug) {
                $providerIds[match ($slug) {
                    'store-delivery' => $storeDelivery->id,
                    'sarie-banha' => $fast->id,
                    'waseet-express' => $cheap->id,
                }] = ['is_enabled' => true];
            }
            $seller->shippingProviders()->sync($providerIds);

            // Self-delivery pricing belongs to the store, so it lives as a
            // seller-scoped rate that beats any platform rate.
            if (isset($data['own_delivery'])) {
                foreach ($data['own_delivery'] as $zoneSlug => $config) {
                    ShippingRate::updateOrCreate(
                        [
                            'shipping_provider_id' => $storeDelivery->id,
                            'shipping_zone_id' => $zones[$zoneSlug],
                            'seller_id' => $seller->id,
                        ],
                        [
                            'price_cents' => $config['price'] * 100,
                            'free_over_cents' => isset($config['free_over']) ? $config['free_over'] * 100 : null,
                            'eta_min_hours' => $config['eta'][0],
                            'eta_max_hours' => $config['eta'][1],
                            'same_day_cutoff' => $config['cutoff'] ?? null,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->createAdmin();
        $this->createDemoCustomer();
    }

    private function stores(): array
    {
        return [
            [
                'name' => 'الشربيني للموبايلات',
                'slug' => 'el-sherbiny-mobiles',
                'owner' => 'أحمد الشربيني',
                'email' => 'sherbiny@banha.shop',
                'phone' => '01001234567',
                'address' => 'شارع فريد ندا، أمام بنك مصر',
                'zone' => 'banha-center',
                'verified' => true,
                'description' => 'محل موبايلات وإكسسوارات في وسط بنها منذ 2009، توصيل بمندوب المحل داخل المدينة.',
                'serves' => ['banha-center', 'new-banha', 'kafr-elgazzar', 'mit-rady'],
                'providers' => ['store-delivery', 'sarie-banha'],
                'own_delivery' => [
                    'banha-center' => ['price' => 20, 'eta' => [2, 5], 'cutoff' => '18:00:00', 'free_over' => 15000],
                    'new-banha' => ['price' => 30, 'eta' => [3, 8], 'cutoff' => '17:00:00'],
                    'kafr-elgazzar' => ['price' => 35, 'eta' => [4, 10]],
                    'mit-rady' => ['price' => 35, 'eta' => [4, 10]],
                ],
            ],
            [
                'name' => 'بنها موبايل سنتر',
                'slug' => 'banha-mobile-center',
                'owner' => 'محمود عبد الله',
                'email' => 'bmc@banha.shop',
                'phone' => '01112345678',
                'address' => 'ميدان المحطة، برج النيل',
                'zone' => 'banha-center',
                'verified' => true,
                'description' => 'أسعار جملة للموبايلات والتابلت مع ضمان الوكيل.',
                'serves' => ['banha-center', 'new-banha', 'greater-banha'],
                'providers' => ['sarie-banha', 'waseet-express'],
            ],
            [
                'name' => 'عالم الإلكترونيات',
                'slug' => 'electronics-world',
                'owner' => 'سامح فؤاد',
                'email' => 'ewold@banha.shop',
                'phone' => '01223456789',
                'address' => 'شارع الجلاء، بجوار مدرسة بنها الثانوية',
                'zone' => 'new-banha',
                'verified' => false,
                'description' => 'شاشات وسماعات وأجهزة صوت.',
                'serves' => ['banha-center', 'new-banha', 'kafr-elgazzar'],
                'providers' => ['store-delivery', 'waseet-express'],
                'own_delivery' => [
                    'new-banha' => ['price' => 25, 'eta' => [4, 12]],
                    'banha-center' => ['price' => 30, 'eta' => [5, 14]],
                    'kafr-elgazzar' => ['price' => 40, 'eta' => [6, 20]],
                ],
            ],
            [
                'name' => 'الأمين للأجهزة المنزلية',
                'slug' => 'el-amin-appliances',
                'owner' => 'ياسر الأمين',
                'email' => 'elamin@banha.shop',
                'phone' => '01034567890',
                'address' => 'طريق بنها المنصورة، أول كفر الجزار',
                'zone' => 'kafr-elgazzar',
                'verified' => true,
                'description' => 'غسالات وثلاجات وأجهزة منزلية بالتقسيط والكاش، توصيل وتركيب.',
                'serves' => ['banha-center', 'new-banha', 'kafr-elgazzar', 'mit-rady', 'greater-banha'],
                'providers' => ['store-delivery'],
                'own_delivery' => [
                    'banha-center' => ['price' => 50, 'eta' => [24, 48], 'free_over' => 1500000],
                    'new-banha' => ['price' => 50, 'eta' => [24, 48], 'free_over' => 1500000],
                    'kafr-elgazzar' => ['price' => 35, 'eta' => [12, 36], 'free_over' => 1000000],
                    'mit-rady' => ['price' => 60, 'eta' => [24, 48]],
                    'greater-banha' => ['price' => 90, 'eta' => [48, 72]],
                ],
            ],
            [
                'name' => 'سوبر ستور بنها',
                'slug' => 'super-store-banha',
                'owner' => 'هالة منصور',
                'email' => 'superstore@banha.shop',
                'phone' => '01145678901',
                'address' => 'بنها الجديدة، المنطقة التجارية',
                'zone' => 'new-banha',
                'verified' => false,
                'description' => 'تشكيلة واسعة من الأجهزة المنزلية والإلكترونيات.',
                'serves' => ['new-banha', 'banha-center', 'mit-rady', 'greater-banha'],
                'providers' => ['sarie-banha', 'waseet-express'],
            ],
            [
                'name' => 'تكنو هاوس',
                'slug' => 'techno-house',
                'owner' => 'كريم رأفت',
                'email' => 'technohouse@banha.shop',
                'phone' => '01256789012',
                'address' => 'شارع المستشفى، بنها',
                'zone' => 'banha-center',
                'verified' => false,
                'description' => 'لابتوبات وإكسسوارات كمبيوتر.',
                'serves' => ['banha-center', 'new-banha'],
                'providers' => ['store-delivery', 'sarie-banha', 'waseet-express'],
                'own_delivery' => [
                    'banha-center' => ['price' => 25, 'eta' => [3, 9], 'cutoff' => '17:00:00'],
                    'new-banha' => ['price' => 35, 'eta' => [5, 12]],
                ],
            ],
        ];
    }

    private function createAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@banha.shop'],
            [
                'name' => 'مشرف المنصة',
                'phone' => '01000000000',
                'password' => 'password',
                'role' => UserRole::Admin,
                'is_active' => true,
            ]
        );
    }

    private function createDemoCustomer(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'customer@banha.shop'],
            [
                'name' => 'منى صلاح',
                'phone' => '01099887766',
                'password' => 'password',
                'role' => UserRole::Customer,
                'is_active' => true,
            ]
        );

        $zoneId = DB::table('shipping_zones')->where('slug', 'banha-center')->value('id');

        $user->addresses()->updateOrCreate(
            ['street' => 'شارع فريد ندا'],
            [
                'shipping_zone_id' => $zoneId,
                'label' => 'البيت',
                'recipient_name' => 'منى صلاح',
                'phone' => '01099887766',
                'building' => '14',
                'floor' => '3',
                'apartment' => '6',
                'landmark' => 'أعلى صيدلية النور',
                'is_default' => true,
            ]
        );
    }
}
