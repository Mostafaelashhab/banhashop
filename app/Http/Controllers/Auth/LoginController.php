<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartManager;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(SeoData $seo): View
    {
        $seo->title('تسجيل الدخول')->noindex(follow: false);

        return view('pages.auth.login');
    }

    public function store(Request $request, CartManager $carts): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Egyptian customers think in phone numbers; accept either.
        $field = preg_match('/^01[0-9]{9}$/', $credentials['identifier']) ? 'phone' : 'email';

        $ok = Auth::attempt(
            [$field => $credentials['identifier'], 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $ok) {
            throw ValidationException::withMessages([
                'identifier' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        $request->session()->regenerate();
        $carts->mergeGuestCartInto($request->user());

        return redirect()->intended($this->homeFor($request));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function homeFor(Request $request): string
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isSeller() => route('seller.dashboard'),
            default => route('home'),
        };
    }
}
