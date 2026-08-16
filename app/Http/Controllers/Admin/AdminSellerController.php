<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SellerStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Models\User;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Early sellers are onboarded by hand, on purpose. This screen exists so an
 * admin can create a working store account in one form while sitting in the
 * shop — no self-service funnel required to get the first stores live.
 */
class AdminSellerController extends Controller
{
    public function index(Request $request, SeoData $seo): View
    {
        $seo->title('المتاجر')->noindex(follow: false);

        $sellers = Seller::query()
            ->with(['user:id,name,email,phone', 'primaryLocation.zone:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('active_offers_count')
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.sellers', [
            'sellers' => $sellers,
            'statuses' => SellerStatus::cases(),
        ]);
    }

    public function create(SeoData $seo): View
    {
        $seo->title('إضافة متجر')->noindex(follow: false);

        return view('pages.admin.seller-create', [
            'zones' => ShippingZone::query()->active()->get(),
            'providers' => ShippingProvider::query()->active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'address_line' => ['required', 'string', 'max:220'],
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'zones' => ['array'],
            'zones.*' => ['integer', 'exists:shipping_zones,id'],
            'providers' => ['array'],
            'providers.*' => ['integer', 'exists:shipping_providers,id'],
            'is_verified' => ['nullable', 'boolean'],
        ], [
            'phone.regex' => 'رقم الموبايل يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
        ]);

        $seller = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'role' => UserRole::Seller,
                'is_active' => true,
            ]);

            $seller = Seller::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'description' => $validated['description'] ?? null,
                'phone' => $validated['phone'],
                'status' => SellerStatus::Active,
                'is_verified' => (bool) ($validated['is_verified'] ?? false),
                'onboarded_by_admin' => true,
                'meta_description' => 'تصفح عروض '.$validated['name'].' في '.config('banha.city')
                    .' وقارن السعر النهائي شامل التوصيل.',
            ]);

            $seller->locations()->create([
                'shipping_zone_id' => $validated['shipping_zone_id'],
                'label' => 'الفرع الرئيسي',
                'address_line' => $validated['address_line'],
                'is_primary' => true,
            ]);

            $seller->zones()->sync($validated['zones'] ?? [$validated['shipping_zone_id']]);
            $seller->shippingProviders()->sync(
                collect($validated['providers'] ?? [])->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
            );

            return $seller;
        });

        return redirect()->route('admin.sellers.index')
            ->with('status', 'تم إنشاء متجر "'.$seller->name.'" وحساب الدخول الخاص به.');
    }

    public function edit(Seller $seller, SeoData $seo): View
    {
        $seo->title('تعديل متجر')->noindex(follow: false);

        $seller->load(['user:id,name,email,phone', 'primaryLocation', 'zones:id', 'shippingProviders:id']);

        return view('pages.admin.seller-edit', [
            'seller' => $seller,
            'zones' => ShippingZone::query()->active()->get(),
            'providers' => ShippingProvider::query()->active()->get(),
            'statuses' => SellerStatus::cases(),
        ]);
    }

    public function update(Request $request, Seller $seller): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'status' => ['required', Rule::enum(SellerStatus::class)],
            'is_verified' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'zones' => ['array'],
            'zones.*' => ['integer', 'exists:shipping_zones,id'],
            'providers' => ['array'],
            'providers.*' => ['integer', 'exists:shipping_providers,id'],
        ]);

        DB::transaction(function () use ($seller, $validated) {
            $seller->update([
                'name' => $validated['name'],
                'status' => SellerStatus::from($validated['status']),
                'is_verified' => (bool) ($validated['is_verified'] ?? false),
                'description' => $validated['description'] ?? null,
            ]);

            $seller->zones()->sync($validated['zones'] ?? []);
            $seller->shippingProviders()->sync(
                collect($validated['providers'] ?? [])->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
            );
        });

        return redirect()->route('admin.sellers.index')->with('status', 'تم تحديث بيانات المتجر.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'store';
        $slug = $base;
        $suffix = 2;

        while (Seller::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
