<?php

namespace App\Http\Controllers;

use App\Support\Seo\SeoData;
use Illuminate\View\View;

class PageController extends Controller
{
    public function howItWorks(SeoData $seo): View
    {
        $seo->title('كيف تعمل بنها شوب')
            ->description('منتج واحد في الكتالوج، عروض متعددة من متاجر '.config('banha.city').'، ومقارنة واضحة للسعر النهائي شامل التوصيل.')
            ->canonical(route('pages.how-it-works'))
            ->breadcrumbs([
                ['label' => 'الرئيسية', 'url' => route('home')],
                ['label' => 'كيف تعمل المنصة', 'url' => route('pages.how-it-works')],
            ]);

        return view('pages.how-it-works');
    }

    public function sell(SeoData $seo): View
    {
        $seo->title('انضم كتاجر في بنها شوب')
            ->description('اعرض منتجاتك على عملاء '.config('banha.city').' وأدر أسعارك ومخزونك وطلباتك من لوحة تحكم بسيطة.')
            ->canonical(route('sell'))
            ->breadcrumbs([
                ['label' => 'الرئيسية', 'url' => route('home')],
                ['label' => 'انضم كتاجر', 'url' => route('sell')],
            ]);

        return view('pages.sell');
    }
}
