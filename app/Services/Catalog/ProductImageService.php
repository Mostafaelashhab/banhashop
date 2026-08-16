<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\Images\ResponsiveImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns an uploaded photograph into the WebP renditions the storefront serves.
 *
 * GD ships with WebP support in this environment, so there is no image package
 * here on purpose: a dependency earns its place by solving a problem the
 * platform actually has, and re-encoding a few catalog photos is not one.
 *
 * Originals are deliberately not kept. They are phone-camera JPEGs several
 * megabytes each, nothing in the product re-crops them, and storing them would
 * cost disk for a capability no screen uses.
 */
class ProductImageService
{
    /** Above this the file is a camera original, not a catalog image. */
    public const MAX_UPLOAD_KB = 8192;

    public const ACCEPTED_MIMES = ['jpeg', 'jpg', 'png', 'webp'];

    private const QUALITY = 82;

    private const DISK = 'public';

    public function store(
        Product $product,
        UploadedFile $file,
        ?string $alt = null,
        ?string $credit = null,
    ): ProductImage {
        $source = $this->decode($file);

        try {
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);

            $targets = array_values(array_filter(
                ResponsiveImage::WIDTHS,
                fn (int $w) => $w <= $sourceWidth,
            ));

            // Never upscale: a 320px photo stays 320px rather than being blown
            // up to 1200 and served as if it were detailed.
            if ($targets === []) {
                $targets = [$sourceWidth];
            }

            $stem = 'products/'.$product->id.'/'.Str::lower(Str::random(16));
            $widest = max($targets);

            foreach ($targets as $width) {
                $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));

                Storage::disk(self::DISK)->put(
                    $stem.'-'.$width.'.webp',
                    $this->encode($source, $width, $height),
                );
            }

            return DB::transaction(function () use ($product, $stem, $widest, $sourceWidth, $sourceHeight, $alt, $credit) {
                $image = $product->images()->create([
                    'path' => $stem.'-'.$widest.'.webp',
                    'alt' => $alt ?: $product->displayName(),
                    'credit' => $credit,
                    'width' => $widest,
                    'height' => (int) round($sourceHeight * ($widest / $sourceWidth)),
                    'position' => (int) $product->images()->max('position') + 1,
                ]);

                // The first image a product ever gets becomes its cover, so the
                // catalog is never left with images but no card thumbnail.
                if (! $product->image_path) {
                    $this->makeCover($product, $image);
                }

                return $image;
            });
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * products.image_path is the denormalised cover that card queries select,
     * so it is only ever written here, alongside the position that decides
     * gallery order.
     */
    public function makeCover(Product $product, ProductImage $image): void
    {
        DB::transaction(function () use ($product, $image) {
            $product->images()
                ->where('id', '!=', $image->id)
                ->where('position', '<=', 0)
                ->increment('position');

            $image->update(['position' => 0]);
            $product->update(['image_path' => $image->path]);
        });
    }

    public function delete(Product $product, ProductImage $image): void
    {
        $responsive = ResponsiveImage::fromPath($image->path);

        Storage::disk(self::DISK)->delete(
            $responsive?->allPaths() ?? [$image->path],
        );

        $wasCover = $product->image_path === $image->path;

        $image->delete();

        if ($wasCover) {
            // Promote the next image rather than leaving the card blank while
            // the product still has photographs.
            $next = $product->images()->orderBy('position')->orderBy('id')->first();

            $next
                ? $this->makeCover($product, $next)
                : $product->update(['image_path' => null]);
        }
    }

    /** @return \GdImage */
    private function decode(UploadedFile $file)
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('تعذّر قراءة ملف الصورة.');
        }

        return $this->applyExifOrientation($image, $file);
    }

    /**
     * Phone cameras record rotation in EXIF rather than in the pixels, and GD
     * reads the pixels. Without this, portrait photos land sideways.
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function applyExifOrientation($image, UploadedFile $file)
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'], true)) {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? null;

        $degrees = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => null,
        };

        if ($degrees === null) {
            return $image;
        }

        $rotated = imagerotate($image, $degrees, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /** @param  \GdImage  $source */
    private function encode($source, int $width, int $height): string
    {
        $canvas = imagecreatetruecolor($width, $height);

        try {
            // PNG logos and pack shots arrive with transparency; WebP keeps it,
            // but only if the canvas is prepared for it before the resample.
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefilledrectangle(
                $canvas, 0, 0, $width, $height,
                imagecolorallocatealpha($canvas, 0, 0, 0, 127),
            );

            imagecopyresampled(
                $canvas, $source,
                0, 0, 0, 0,
                $width, $height,
                imagesx($source), imagesy($source),
            );

            ob_start();
            imagewebp($canvas, null, self::QUALITY);

            return (string) ob_get_clean();
        } finally {
            imagedestroy($canvas);
        }
    }
}
