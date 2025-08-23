@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
    <div class="container">
        <h1>メール認証が必要です</h1>
        <p>メールを確認し、認証を完了してください。</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit">確認メールを再送</button>
        </form>
    </div>
@endsection