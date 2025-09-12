@extends('admin.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
    <div class="login-container">
        <form class="login-form" action="/login" method="post">
            @csrf
            <h2 class="login-title">管理者ログイン</h2>
            <div class="login-form__group">
                <label class="login-form__label" for="name">メールアドレス</label>
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
            <button class="login-button" type="submit">管理者ログインする</button>
        </form>
    </div>
@endsection