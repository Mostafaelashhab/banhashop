<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Support\Images\ResponsiveImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** A real JPEG, so the service exercises GD rather than a fake file. */
    private function upload(int $width, int $height, string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    public function test_an_upload_produces_webp_renditions_and_becomes_the_cover(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.images.store', $product), [
                'images' => [$this->upload(1600, 1200)],
            ])
            ->assertRedirect();

        $image = $product->images()->sole();

        // The ladder is capped by the source, and the widest one is what the
        // stored path points at.
        $this->assertSame(1200, $image->width);
        $this->assertSame(900, $image->height);
        $this->assertStringEndsWith('-1200.webp', $image->path);

        foreach (ResponsiveImage::WIDTHS as $width) {
            Storage::disk('public')->assertExists(
                ResponsiveImage::fromPath($image->path)->pathFor($width)
            );
        }

        // The first image a product gets is its card thumbnail.
        $this->assertSame($image->path, $product->fresh()->image_path);
    }

    public function test_a_small_source_is_never_upscaled(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.images.store', $product), [
                'images' => [$this->upload(320, 320)],
            ]);

        $image = $product->images()->sole();

        $this->assertSame(320, $image->width);
        $this->assertSame([320], ResponsiveImage::fromPath($image->path)->widths());
        Storage::disk('public')->assertMissing(
            ResponsiveImage::fromPath($image->path)->pathFor(1200)
        );
    }

    public function test_deleting_the_cover_promotes_the_next_image_and_removes_every_rendition(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.products.images.store', $product), [
            'images' => [$this->upload(1600, 1200, 'first.jpg')],
        ]);
        $this->actingAs($admin)->post(route('admin.products.images.store', $product), [
            'images' => [$this->upload(1600, 1200, 'second.jpg')],
        ]);

        $cover = $product->images()->orderBy('position')->first();
        $paths = ResponsiveImage::fromPath($cover->path)->allPaths();

        $this->actingAs($admin)
            ->delete(route('admin.products.images.destroy', [$product, $cover]))
            ->assertRedirect();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }

        $remaining = $product->images()->sole();
        $this->assertSame($remaining->path, $product->fresh()->image_path);
    }

    public function test_the_last_image_leaves_the_product_without_a_cover(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.products.images.store', $product), [
            'images' => [$this->upload(800, 800)],
        ]);

        $this->actingAs($admin)->delete(
            route('admin.products.images.destroy', [$product, $product->images()->sole()])
        );

        $this->assertNull($product->fresh()->image_path);
        $this->assertSame(0, $product->images()->count());
    }

    public function test_the_cover_can_be_switched(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();
        $admin = $this->admin();

        foreach (['a.jpg', 'b.jpg'] as $name) {
            $this->actingAs($admin)->post(route('admin.products.images.store', $product), [
                'images' => [$this->upload(800, 800, $name)],
            ]);
        }

        $second = $product->images()->orderByDesc('position')->first();

        $this->actingAs($admin)
            ->patch(route('admin.products.images.update', [$product, $second]), [
                'alt' => 'من الخلف',
                'cover' => 1,
            ])
            ->assertRedirect();

        $this->assertSame($second->path, $product->fresh()->image_path);
        $this->assertSame('من الخلف', $second->fresh()->alt);
        $this->assertSame(0, $second->fresh()->position);
    }

    public function test_only_an_admin_may_attach_catalog_photography(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();

        $this->actingAs(User::factory()->seller()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [$this->upload(800, 800)],
            ])
            ->assertForbidden();

        $this->assertSame(0, $product->images()->count());
    }

    public function test_an_image_cannot_be_moved_between_products(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $owner = Product::factory()->create();
        $other = Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.images.store', $owner), [
            'images' => [$this->upload(800, 800)],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.images.destroy', [$other, $owner->images()->sole()]))
            ->assertNotFound();
    }

    public function test_the_storefront_serves_a_responsive_srcset(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['name' => 'منتج بصورة']);

        $this->actingAs($this->admin())->post(route('admin.products.images.store', $product), [
            'images' => [$this->upload(1600, 1200)],
        ]);

        $this->get($product->fresh()->url())
            ->assertOk()
            ->assertSee('-400.webp', escape: false)
            ->assertSee('-1200.webp', escape: false)
            ->assertSee('srcset', escape: false)
            ->assertSee('sizes', escape: false);
    }
}
