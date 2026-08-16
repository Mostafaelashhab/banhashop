<x-layouts.app>
    <div class="auth">
        <h1 class="auth__title">تسجيل الدخول</h1>
        <p class="auth__sub">ادخل بحسابك عشان تكمل الطلب وتتابع حالته.</p>

        <div class="panel">
            <div class="panel__body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <x-ui.field
                        name="identifier"
                        label="البريد الإلكتروني أو رقم الموبايل"
                        required
                        autocomplete="username"
                    />

                    <x-ui.field name="password" label="كلمة المرور" type="password" required autocomplete="current-password" />

                    <label class="check" style="margin-block-start:12px">
                        <input type="checkbox" name="remember" value="1">
                        <span>تذكرني على هذا الجهاز</span>
                    </label>

                    <button type="submit" class="btn btn--primary btn--lg btn--block" style="margin-block-start:16px">
                        دخول
                    </button>
                </form>
            </div>
        </div>

        <p class="auth__foot">
            ملكش حساب؟ <a href="{{ route('register') }}">أنشئ حساب جديد</a>
        </p>
    </div>
</x-layouts.app>
