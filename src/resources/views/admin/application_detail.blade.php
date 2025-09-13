@extends('admin.app')

@section('title', '修正申請承認')

@section('content')
    <div class="container">
        <h1>修正申請承認</h1>

        <p>名前 {{ $application->user->name }}</p>
        <p>日付 {{ $application->date->format('Y年n月j日') }}</p>
        <p>出勤・退勤 {{ $application->clock_in?->format('H:i') }}</p>
        <p>{{ $application->clock_out?->format('H:i') }}</p>

        <h3>休憩</h3>
        @foreach ($application->items as $item)
            <p>{{ $item->start->format('H:i') }} ～ {{ $item->end->format('H:i') }}</p>
        @endforeach

        <h3>備考</h3>
        <p>{{ $application->reason }}</p>

        @if ($application->status === 'pending')
            <form method="POST" action="{{ route('admin.application.approve', $application->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary">承認</button>
            </form>
        @elseif ($application->status === 'approved')
            <p class="text-success">承認済み</p>
        @endif

    </div>
@endsection