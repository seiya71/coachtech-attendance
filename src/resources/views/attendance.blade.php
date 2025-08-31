@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
    <div class="container">
        <h2>本日: {{ $today }}（{{ $time }}）</h2>

        <p>現在のステータス：<strong>{{ $status }}</strong></p>

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($status === '勤務外')
            <form method="POST" action="{{ route('me.attendance.clock_in') }}">
                @csrf
                <button class="btn btn-primary">出勤</button>
            </form>

        @elseif ($status === '勤務中')
            <form method="POST" action="{{ route('me.attendance.break_in') }}" class="d-inline">
                @csrf
                <button class="btn btn-warning">休憩開始</button>
            </form>

            <form method="POST" action="{{ route('me.attendance.clock_out') }}" class="d-inline">
                @csrf
                <button class="btn btn-danger">退勤</button>
            </form>

        @elseif ($status === '休憩中')
            <form method="POST" action="{{ route('me.attendance.break_out') }}">
                @csrf
                <button class="btn btn-success">休憩終了</button>
            </form>

        @elseif ($status === '退勤済')
            <p class="text-success">本日の勤務は終了しています。お疲れ様でした！</p>
        @endif
    </div>
@endsection