<div class="login-container">
    <form class="login-form" action="{{ $action }}" method="post">
        @csrf
        <h2 class="login-title">{{ $title }}</h2>

        <div class="login-form__group">
            <label class="login-form__label" for="email">メールアドレス</label>
            <input class="login-form__input" type="email" id="email" name="email">
            @error('email')
                <p>{{ $message }}</p>
            @enderror
        </div>

        <div class="login-form__group">
            <label class="login-form__label" for="password">パスワード</label>
            <input class="login-form__input" type="password" id="password" name="password">
            @error('password')
                <p>{{ $message }}</p>
            @enderror
        </div>

        <button class="login-button" type="submit">{{ $button }}</button>

        @isset($showRegister)
            <a class="register-link" href="{{ route('register') }}">会員登録はこちら</a>
        @endisset
    </form>
</div>