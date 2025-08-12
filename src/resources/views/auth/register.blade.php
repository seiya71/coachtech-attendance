@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
    <div class="register-container">
        <form class="register-form" action="/register" method="post">
            @csrf
            <h2 class="register-title">会員登録</h2>
            <div class="register-form__group">
                <label class="register-form__label" for="name">名前</label>
                <input class="register-form__input" type="text" id="name" name="name" value="{{ old('name') }}">
                @error('name')
                    <p>{{ $message }}</p>
                @enderror
            </div>
            <div class="register-form__group">
                <label class="register-form__label" for="email">メールアドレス</label>
                <input class="register-form__input" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <p>{{ $message }}</p>
                @enderror
            </div>
            <div class="register-form__group">
                <label class="register-form__label" for="password">パスワード</label>
                <input class="register-form__input" type="password" id="password" name="password">
                @error('password')
                    <p>{{ $message }}</p>
                @enderror
            </div>
            <div class="register-form__group">
                <label class="register-form__label" for="password_confirmation">パスワード確認</label>
                <input class="register-form__input" type="password" id="password_confirmation" name="password_confirmation">
            </div>
            <button class="register-button" type="submit">登録する</button>
            <a class="login-link" href="/login">ログインはこちら</a>
        </form>
    </div>
@endsection