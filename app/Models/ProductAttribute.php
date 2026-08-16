<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Free-form, category-specific specs. Mobiles get "الشاشة / 6.3 بوصة",
 * appliances get "السعة / 12 كيلو" — without forcing one rigid schema.
 */
#[Fillable(['product_id', 'name', 'value', 'position'])]
class ProductAttribute extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
