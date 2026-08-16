<?php

namespace App\Support\Images;

/**
 * Derives a responsive srcset from a stored image path.
 *
 * Variants are written as `<stem>-<width>.webp`, so the widest one that exists
 * is readable from the filename itself. That keeps card rendering free of an
 * extra relation and an extra query per product — a catalog grid renders 24 of
 * these, and a join to product_images for dimensions we already encoded in the
 * name would be a self-inflicted N+1.
 *
 * Anything that does not match the pattern — an external URL, or a path
 * imported before this pipeline existed — yields null and the caller falls
 * back to a plain <img src>.
 */
final class ResponsiveImage
{
    /** Card ~300px, product hero ~560px, and a 2x tier above it. */
    public const WIDTHS = [400, 800, 1200];

    private function __construct(
        private readonly string $stem,
        private readonly string $extension,
        private readonly int $maxWidth,
    ) {}

    public static function fromPath(?string $path): ?self
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (! preg_match('/^(?<stem>.+)-(?<width>\d+)\.(?<ext>[a-zA-Z0-9]+)$/', $path, $matches)) {
            return null;
        }

        $width = (int) $matches['width'];

        return $width > 0 ? new self($matches['stem'], $matches['ext'], $width) : null;
    }

    /**
     * Every width actually generated for this image. The service never upscales,
     * so a small source simply has fewer variants — and its own width is always
     * one of them even when it falls outside the standard ladder.
     *
     * @return array<int, int>
     */
    public function widths(): array
    {
        $widths = array_filter(self::WIDTHS, fn (int $w) => $w <= $this->maxWidth);

        if (! in_array($this->maxWidth, $widths, true)) {
            $widths[] = $this->maxWidth;
        }

        sort($widths);

        return array_values($widths);
    }

    public function maxWidth(): int
    {
        return $this->maxWidth;
    }

    public function pathFor(int $width): string
    {
        return $this->stem.'-'.$width.'.'.$this->extension;
    }

    public function path(): string
    {
        return $this->pathFor($this->maxWidth);
    }

    /** Every variant path, for deleting an image and all of its renditions. */
    public function allPaths(): array
    {
        return array_map(fn (int $w) => $this->pathFor($w), $this->widths());
    }

    /** @param  callable(string): string  $url  Maps a stored path to a public URL. */
    public function srcset(callable $url): string
    {
        return implode(', ', array_map(
            fn (int $w) => $url($this->pathFor($w)).' '.$w.'w',
            $this->widths(),
        ));
    }
}
