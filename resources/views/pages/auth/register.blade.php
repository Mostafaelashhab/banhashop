<x-layouts.app>
    <div class="auth">
        <h1 class="auth__title">إنشاء حساب</h1>
        <p class="auth__sub">حساب واحد يكفي عشان تطلب وتتابع طلباتك من متاجر {{ config('banha.city') }}.</p>

        <div class="panel">
            <div class="panel__body">
                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <x-ui.field name="name" label="الاسم" required autocomplete="name" />
                    <x-ui.field name="phone" label="رقم الموبايل" required inputmode="tel"
                                placeholder="01xxxxxxxxx" autocomplete="tel" />
                    <x-ui.field name="email" label="البريد الإلكتروني" type="email" required autocomplete="email" />
                    <x-ui.field name="password" label="كلمة المرور" type="password" required
                                autocomplete="new-password" hint="8 أحرف على الأقل." />
                    <x-ui.field name="password_confirmation" label="تأكيد كلمة المرور" type="password" required
                                autocomplete="new-password" />

                    <button type="submit" class="btn btn--primary btn--lg btn--block" style="margin-block-start:16px">
                        إنشاء الحساب
                    </button>
                </form>
            </div>
        </div>

        <p class="auth__foot">
            عندك حساب بالفعل؟ <a href="{{ route('login') }}">سجّل الدخول</a>
        </p>
    </div>
</x-layouts.app>
