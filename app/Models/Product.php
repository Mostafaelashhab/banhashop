<?php

namespace App\Models;

use App\Enums\OfferStatus;
use App\Enums\ProductStatus;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * The single source of truth for "what a thing is". Price, stock and
 * availability belong to SellerOffer — never to the product.
 */
#[Fillable([
    'parent_id', 'category_id', 'brand_id', 'name', 'slug', 'variant_label',
    'model', 'mpn', 'barcode', 'description', 'highlights', 'image_path',
    'weight_grams', 'status', 'rejection_reason', 'meta_title',
    'meta_description', 'search_keywords', 'created_by', 'approved_by', 'published_at',
])]
#[RouteKey('slug')]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'status' => ProductStatus::class,
            'published_at' => 'datetime',
            'offers_count' => 'integer',
            'sellers_count' => 'integer',
            'min_price_cents' => 'integer',
            'max_price_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if (blank($product->slug)) {
                $product->slug = self::uniqueSlug($product->name, $product->variant_label);
            }

            $product->search_text = $product->buildSearchText();
        });
    }

    public static function uniqueSlug(string $name, ?string $variant = null): string
    {
        $base = Str::slug(trim($name.' '.$variant), '-', null) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (self::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * The normalised haystack behind the FULLTEXT index. Arabic and Latin
     * spellings of the same product must collapse onto the same tokens.
     */
    public function buildSearchText(): string
    {
        $brand = $this->relationLoaded('brand')
            ? $this->brand
            : ($this->brand_id ? Brand::find($this->brand_id) : null);

        $category = $this->relationLoaded('category')
            ? $this->category
            : ($this->category_id ? Category::find($this->category_id) : null);

        $parts = [
            $this->name,
            $this->variant_label,
            $this->model,
            $this->mpn,
            $this->barcode,
            $brand?->name,
            // "سامسونج" must find Samsung products.
            $brand?->name_ar,
            $category?->name,
            $this->search_keywords,
        ];

        return ArabicText::normalize(implode(' ', array_filter($parts)));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * `parent_id` is a variant-group key, not a hierarchy: every member of a
     * group carries the same value and the group head points at itself. That
     * keeps "give me all capacities of this phone" a single indexed lookup.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('position');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SellerOffer::class);
    }

    public function activeOffers(): HasMany
    {
        return $this->hasMany(SellerOffer::class)
            ->where('status', OfferStatus::Active)
            ->where('stock', '>', 0);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    #[Scope]
    protected function withOffers(Builder $query): void
    {
        $query->where('offers_count', '>', 0);
    }

    /** Everything a product card needs, in one pass. No N+1. */
    #[Scope]
    protected function forCard(Builder $query): void
    {
        $query->select([
            'id', 'name', 'slug', 'variant_label', 'image_path', 'brand_id',
            'category_id', 'offers_count', 'sellers_count', 'min_price_cents',
            'max_price_cents',
        ])->with(['brand:id,name']);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    public function hasOffers(): bool
    {
        return $this->offers_count > 0;
    }

    public function url(): string
    {
        return route('products.show', $this->slug);
    }

    public function displayName(): string
    {
        return trim($this->name.' '.($this->variant_label ? '— '.$this->variant_label : ''));
    }
}
