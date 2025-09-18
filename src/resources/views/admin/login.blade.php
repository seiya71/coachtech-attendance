@extends('admin.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
    @include('auth.partials.login_form', [
        'action' => route('admin.login.form'),
        'title' => '管理者ログイン',
        'button' => '管理者ログインする'
    ])
@endsection