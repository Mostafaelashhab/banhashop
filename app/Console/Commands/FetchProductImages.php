<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductImageService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Imports catalog photography from a manifest of URLs.
 *
 * The sibling command reads a folder; this one reads a list, which is what you
 * want when the images live at a supplier's or manufacturer's URL rather than
 * on your disk. Both end up in the same WebP pipeline.
 *
 * Manifest is JSON:
 *
 *   [
 *     {
 *       "slug":   "galaxy-tab-a9-plus-128gb",
 *       "url":    "https://…/photo.jpg",
 *       "alt":    "تابلت سامسونج جالاكسي تاب A9+",
 *       "credit": "JGBlue1509, CC0, via Wikimedia Commons"
 *     }
 *   ]
 *
 * `credit` is optional and belongs to the licence, not to us: leave it out for
 * your own photography and for CC0, fill it in for anything under CC BY.
 */
class FetchProductImages extends Command
{
    protected $signature = 'catalog:fetch-images
        {manifest : Path to a JSON manifest of {slug, url, alt?, credit?} entries}
        {--dry-run : Resolve and report without downloading or writing}';

    protected $description = 'Import product photography listed in a JSON manifest of URLs';

    private const MAX_BYTES = ProductImageService::MAX_UPLOAD_KB * 1024;

    public function handle(ProductImageService $images): int
    {
        $path = $this->argument('manifest');

        if (! is_file($path)) {
            $this->error("No manifest at {$path}");

            return self::FAILURE;
        }

        $entries = json_decode((string) file_get_contents($path), true);

        if (! is_array($entries)) {
            $this->error('Manifest is not valid JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $imported = 0;
        $problems = [];

        foreach ($entries as $i => $entry) {
            $label = $entry['slug'] ?? "entry #{$i}";

            if (empty($entry['slug']) || empty($entry['url'])) {
                $problems[] = "{$label}: needs both slug and url";

                continue;
            }

            $product = Product::where('slug', $entry['slug'])->first();

            if (! $product) {
                $problems[] = "{$label}: no product with that slug";

                continue;
            }

            if ($dryRun) {
                $this->line("  would fetch {$entry['url']}");
                $this->line("            -> {$product->displayName()}");
                $imported++;

                continue;
            }

            try {
                $file = $this->download($entry['url']);
            } catch (Throwable $e) {
                $problems[] = "{$label}: ".$e->getMessage();

                continue;
            }

            try {
                $image = $images->store($product, $file, $entry['alt'] ?? null, $entry['credit'] ?? null);
                $this->line("  {$product->displayName()} <- {$image->width}px".
                    (isset($entry['credit']) ? '  ['.$entry['credit'].']' : ''));
                $imported++;
            } catch (Throwable $e) {
                $problems[] = "{$label}: ".$e->getMessage();
            } finally {
                @unlink($file->getRealPath());
            }
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would import ' : 'Imported ').$imported.' of '.count($entries).' entry(ies).');

        foreach ($problems as $problem) {
            $this->warn('  '.$problem);
        }

        $without = Product::whereNull('image_path')->count();

        if ($without > 0) {
            $this->newLine();
            $this->warn($without.' product(s) still have no image.');
        }

        return self::SUCCESS;
    }

    private function download(string $url): UploadedFile
    {
        // Wikimedia and most CDNs reject a request with no User-Agent, and a
        // default Guzzle one gets a 403 rather than a helpful error.
        $response = Http::withHeaders([
            'User-Agent' => 'BanhaShopCatalogBot/1.0 (+https://banha.shop)',
        ])->timeout(30)->get($url);

        if (! $response->successful()) {
            throw new ConnectionException("HTTP {$response->status()} fetching the image");
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_BYTES) {
            throw new ConnectionException('Image is larger than '.ProductImageService::MAX_UPLOAD_KB.'KB');
        }

        // Trust the bytes, not the URL or the Content-Type: the decoder is what
        // has to cope with the file, so let it be the thing that decides.
        if (@imagecreatefromstring($body) === false) {
            throw new ConnectionException('Downloaded bytes are not a readable image');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'banha-img-');
        file_put_contents($tmp, $body);

        return new UploadedFile($tmp, basename(parse_url($url, PHP_URL_PATH) ?: 'image.jpg'), null, null, true);
    }
}
