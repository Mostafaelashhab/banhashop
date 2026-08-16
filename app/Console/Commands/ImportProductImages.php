<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductImageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Backfills catalog photography from a folder.
 *
 * The admin form handles a product at a time, which is right for ongoing work
 * and wrong for the first twenty. Name the files after the product they belong
 * to and this walks the folder once:
 *
 *   galaxy-tab-a9-plus-128gb.jpg      -> matched on slug
 *   galaxy-tab-a9-plus-128gb-2.jpg    -> second image for the same product
 *   6221031492107.jpg                 -> matched on barcode with --match=barcode
 *
 * A trailing -1 / -2 / _2 suffix is stripped before matching, so several shots
 * of one product sort together in the folder and import in that order.
 */
class ImportProductImages extends Command
{
    protected $signature = 'catalog:import-images
        {directory : Folder holding the image files}
        {--match=slug : Which product column the filename identifies (slug, barcode, mpn)}
        {--dry-run : Report what would be imported without writing anything}';

    protected $description = 'Import product photography from a folder, matching files to products by name';

    private const MATCHABLE = ['slug', 'barcode', 'mpn'];

    public function handle(ProductImageService $images): int
    {
        $directory = rtrim($this->argument('directory'), '/');
        $column = (string) $this->option('match');
        $dryRun = (bool) $this->option('dry-run');

        if (! in_array($column, self::MATCHABLE, true)) {
            $this->error('--match must be one of: '.implode(', ', self::MATCHABLE));

            return self::FAILURE;
        }

        if (! is_dir($directory)) {
            $this->error("Not a directory: {$directory}");

            return self::FAILURE;
        }

        $files = $this->imageFiles($directory);

        if ($files === []) {
            $this->warn('No JPG, PNG or WebP files found in '.$directory);

            return self::SUCCESS;
        }

        $imported = 0;
        $skipped = [];

        foreach ($files as $file) {
            $key = $this->keyFromFilename($file);
            $product = Product::where($column, $key)->first();

            if (! $product) {
                $skipped[] = basename($file)." (no product with {$column} \"{$key}\")";

                continue;
            }

            if ($dryRun) {
                $this->line("  would import ".basename($file).' -> '.$product->displayName());
                $imported++;

                continue;
            }

            try {
                $image = $images->store($product, $this->asUpload($file));
                $this->line('  '.basename($file).' -> '.$product->displayName()." ({$image->width}px)");
                $imported++;
            } catch (Throwable $e) {
                $skipped[] = basename($file).' ('.$e->getMessage().')';
            }
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would import ' : 'Imported ').$imported.' of '.count($files).' file(s).');

        foreach ($skipped as $reason) {
            $this->warn('  skipped '.$reason);
        }

        // The gap that matters is not how many files imported but how much of
        // the catalog still renders as an empty grey square.
        $without = Product::whereNull('image_path')->count();

        if ($without > 0) {
            $this->newLine();
            $this->warn($without.' published-or-draft product(s) still have no image.');
        }

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function imageFiles(string $directory): array
    {
        $files = glob($directory.'/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE) ?: [];

        // Deterministic order, so "-2" lands after "-1" and gallery position
        // follows the filenames rather than the filesystem.
        sort($files);

        return $files;
    }

    /** Strips the directory, extension, and any -2 / _2 disambiguating suffix. */
    private function keyFromFilename(string $path): string
    {
        $stem = pathinfo($path, PATHINFO_FILENAME);

        return preg_replace('/[-_]\d+$/', '', $stem) ?: $stem;
    }

    private function asUpload(string $path): UploadedFile
    {
        return new UploadedFile($path, basename($path), null, null, true);
    }
}
