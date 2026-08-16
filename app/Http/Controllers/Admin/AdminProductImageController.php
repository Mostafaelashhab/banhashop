<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Product photography belongs to the central catalog, not to a seller: a
 * product exists once and every store attaches an offer to that one record, so
 * its images pass through the same admin gate the product itself does.
 */
class AdminProductImageController extends Controller
{
    public function __construct(private readonly ProductImageService $images) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:8'],
            'images.*' => [
                'file',
                'mimes:'.implode(',', ProductImageService::ACCEPTED_MIMES),
                'max:'.ProductImageService::MAX_UPLOAD_KB,
            ],
            'alt' => ['nullable', 'string', 'max:180'],
        ], [], [
            'images' => 'الصور',
            'images.*' => 'الصورة',
        ]);

        $stored = 0;

        foreach ($validated['images'] as $file) {
            try {
                $this->images->store($product, $file, $validated['alt'] ?? null);
                $stored++;
            } catch (RuntimeException $e) {
                return back()->withErrors(['images' => $e->getMessage()]);
            }
        }

        return back()->with('status', $stored === 1
            ? 'تمت إضافة الصورة.'
            : 'تمت إضافة '.$stored.' صور.');
    }

    public function update(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:180'],
            'credit' => ['nullable', 'string', 'max:180'],
            'cover' => ['nullable', 'boolean'],
        ]);

        $image->update([
            'alt' => $validated['alt'] ?? null,
            'credit' => $validated['credit'] ?? null,
        ]);

        if ($request->boolean('cover')) {
            $this->images->makeCover($product, $image);

            return back()->with('status', 'تم تعيين الصورة الرئيسية.');
        }

        return back()->with('status', 'تم حفظ وصف الصورة.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $this->images->delete($product, $image);

        return back()->with('status', 'تم حذف الصورة.');
    }
}
