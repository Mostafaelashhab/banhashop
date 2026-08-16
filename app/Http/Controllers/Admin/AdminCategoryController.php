<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(SeoData $seo): View
    {
        $seo->title('الأقسام')->noindex(follow: false);

        return view('pages.admin.categories', [
            'roots' => Category::with('children')->whereNull('parent_id')->orderBy('position')->get(),
            'options' => Category::whereNull('parent_id')->orderBy('position')->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Category::create($validated + ['slug' => $this->uniqueSlug($validated['name'])]);
        $this->forgetCaches();

        return back()->with('status', 'تمت إضافة القسم.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request));
        $this->forgetCaches();

        return back()->with('status', 'تم تحديث القسم.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /** The header nav caches the category list; a rename must show up at once. */
    private function forgetCaches(): void
    {
        Cache::forget('nav.categories');
    }
}
