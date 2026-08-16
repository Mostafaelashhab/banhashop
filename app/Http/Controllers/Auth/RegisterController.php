<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(SeoData $seo): View
    {
        $seo->title('إنشاء حساب')->noindex(follow: false);

        return view('pages.auth.register');
    }

    public function store(Request $request, CartManager $carts): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.regex' => 'رقم الموبايل يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
            'phone.unique' => 'هذا الرقم مسجل بالفعل.',
            'email.unique' => 'هذا البريد مسجل بالفعل.',
        ]);

        $user = User::create($validated + ['role' => UserRole::Customer]);

        Auth::login($user);
        $request->session()->regenerate();
        $carts->mergeGuestCartInto($user);

        return redirect()->intended(route('home'))->with('status', 'أهلًا بك في بنها شوب.');
    }
}
