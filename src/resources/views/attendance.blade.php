@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
    <div class="container">
        <p>{{ $status }}</p>
        <p>{{ $date }}</p>
        <p>{{ $time }}</p>

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        @if ($status === '勤務外')
            <form method="POST" action="{{ route('attendance.clock_in') }}">
                @csrf
                <button class="btn btn-primary">出勤</button>
            </form>

        @elseif ($status === '勤務中')
            <form method="POST" action="{{ route('attendance.break_in') }}" class="d-inline">
                @csrf
                <button class="btn btn-warning">休憩入</button>
            </form>

            <form method="POST" action="{{ route('attendance.clock_out') }}" class="d-inline">
                @csrf
                <button class="btn btn-danger">退勤</button>
            </form>

        @elseif ($status === '休憩中')
            <form method="POST" action="{{ route('attendance.break_out') }}">
                @csrf
                <button class="btn btn-success">休憩戻</button>
            </form>

        @elseif ($status === '退勤済')
            <p class="text-success">お疲れ様でした。</p>
        @endif
    </div>
@endsection