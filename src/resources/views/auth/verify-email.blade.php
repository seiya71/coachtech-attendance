@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
    <div class="container">
        <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p>メール認証を完了してください。</p>
        
        @php
            $override = config('webmail.override');
            $map = config('webmail.map', []);
            $fallback = config('webmail.fallback');

            $email = auth()->user()->email ?? '';
            $domain = \Illuminate\Support\Str::after($email, '@');

            $webmailUrl = $override ?: ($map[$domain] ?? $fallback);
        @endphp

        <a href="{{ $webmailUrl }}" target="_blank" rel="noopener">認証はこちらから</a>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit">確認メールを再送する</button>
        </form>
    </div>
@endsection