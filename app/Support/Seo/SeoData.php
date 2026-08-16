<?php

namespace App\Support\Seo;

/**
 * The single SEO object for the current request. Controllers fill it, the
 * <x-seo> component renders it. No page ever hand-writes a meta tag.
 */
class SeoData
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $canonical = null;

    public string $robots = 'index, follow';

    public string $ogType = 'website';

    public ?string $ogImage = null;

    /** @var array<int, array<string, mixed>> */
    public array $structuredData = [];

    /** @var array<int, array{label: string, url: ?string}> */
    public array $breadcrumbs = [];

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description ? trim(preg_replace('/\s+/u', ' ', strip_tags($description))) : null;

        if ($this->description !== null && mb_strlen($this->description) > 300) {
            $this->description = mb_substr($this->description, 0, 297).'…';
        }

        return $this;
    }

    public function canonical(?string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function robots(string $robots): static
    {
        $this->robots = $robots;

        return $this;
    }

    public function noindex(bool $follow = true): static
    {
        return $this->robots('noindex, '.($follow ? 'follow' : 'nofollow'));
    }

    public function ogType(string $type): static
    {
        $this->ogType = $type;

        return $this;
    }

    public function ogImage(?string $url): static
    {
        $this->ogImage = $url;

        return $this;
    }

    /** @param  array<string, mixed>  $schema */
    public function addSchema(array $schema): static
    {
        $this->structuredData[] = $schema;

        return $this;
    }

    /** @param  array<int, array{label: string, url: ?string}>  $trail */
    public function breadcrumbs(array $trail): static
    {
        $this->breadcrumbs = $trail;

        if ($trail !== []) {
            $this->addSchema(JsonLd::breadcrumbs($trail));
        }

        return $this;
    }

    public function fullTitle(): string
    {
        $default = config('banha.seo.default_title');

        if ($this->title === null || $this->title === '') {
            return $default;
        }

        return $this->title.' | '.config('app.name');
    }

    public function metaDescription(): string
    {
        return $this->description ?: config('banha.seo.default_description');
    }

    public function isIndexable(): bool
    {
        return ! str_contains($this->robots, 'noindex');
    }
}
