@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
    @include('auth.partials.login_form', [
        'action' => route('login'),
        'title' => 'ログイン',
        'button' => 'ログインする',
        'showRegister' => true
    ])
@endsection
