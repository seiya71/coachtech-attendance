@extends('layouts.app')

@section('title', '勤怠詳細')

@section('content')
    <div class="container">
        <h1>勤怠詳細</h1>

        <div class="mb-3">
            <label>名前：</label>
            <p class="form-control-plaintext">{{ $attendance->user->name }}</p>
        </div>

        <div class="mb-3">
            <label>日付：</label>
            <p class="form-control-plaintext">{{ $attendance->date->format('Y年') }}</p>
            <p class="form-control-plaintext">{{ $attendance->date->format('n月j日') }}</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($attendance->is_editable)
            <form method="POST" action="{{ route('applications.submit', $attendance->id) }}">
                @csrf

                <input type="hidden" name="date" value="{{ $attendance->date ?? $targetDate ?? '' }}">

                <div class="mb-3">
                    <label>出勤・退勤：</label>
                    <div class="d-flex">
                        <input type="time" name="clock_in"
                            value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}"
                            class="form-control me-2">
                        ～
                        <input type="time" name="clock_out"
                            value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}"
                            class="form-control me-2">
                    </div>
                </div>

                <div class="mb-3">
                    <label>休憩：</label>
                    @foreach ($attendance->breaks as $index => $break)
                        <div class="d-flex mb-2">
                            <input type="time" name="breaks[{{ $index }}][start]"
                                value="{{ old("breaks.$index.start", optional($break->start)->format('H:i')) }}"
                                class="form-control me-2">
                            <input type="time" name="breaks[{{ $index }}][end]"
                                value="{{ old("breaks.$index.end", optional($break->end)->format('H:i')) }}"
                                class="form-control me-2">
                        </div>
                    @endforeach
                    <div class="d-flex">
                        <input type="time" name="breaks[new][start]" class="form-control me-2">
                        ～
                        <input type="time" name="breaks[new][end]" class="form-control ms-2">
                    </div>
                </div>

                <div class="mb-3">
                    <label>備考：</label>
                    <textarea name="reason" class="form-control">{{ old('reason', $attendance->reason) }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">修正</button>
                </div>
            </form>
        @else
            <div class="mb-3">
                <label>出勤・退勤：</label>
                <div class="d-flex">
                    <p class="form-control-plaintext me-2">{{ $attendance->clock_in }}</p>
                    ～
                    <p class="form-control-plaintext ms-2">{{ $attendance->clock_out }}</p>
                </div>
            </div>

            <div class="mb-3">
                <label>休憩：</label>
                @foreach($attendance->breaks as $break)
                    <div class="d-flex mb-1">
                        <p class="form-control-plaintext me-2">{{ $break->start }}</p>
                        ～
                        <p class="form-control-plaintext ms-2">{{ $break->end }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mb-3">
                <label>備考：</label>
                <p class="form-control-plaintext">{{ $attendance->reason }}</p>
            </div>

            <div class="mb-3">
                <div class="alert alert-info">*承認待ちのため修正はできません。</div>
            </div>
        @endif
    </div>
@endsection