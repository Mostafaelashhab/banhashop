<?php

namespace App\View\Components;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The static half of the header. The zone picker and the cart badge are
 * Livewire components, because both have to react to what happens further down
 * the page; the category nav is the same for every visitor and stays cached.
 */
class SiteHeader extends Component
{
    public function render(): View
    {
        return view('components.layout.site-header', [
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Plain arrays, not models: the cache stores data, and the nav has no use
     * for Eloquent behaviour anyway.
     *
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function categories(): array
    {
        return Cache::remember(
            'nav.categories',
            now()->addHour(),
            fn () => Category::query()->active()->roots()->orderBy('position')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])
                ->all()
        );
    }
}
