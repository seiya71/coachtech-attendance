@extends('admin.app')

@section('title', '勤怠詳細')

@section('title', '勤怠詳細（管理者用）')

@section('content')
    <div class="container">
        <h1>勤怠詳細（管理者用）</h1>

        <div class="mb-3">
            <label>名前：</label>
            <p class="form-control-plaintext">{{ $attendance->user->name }}</p>
        </div>

        <div class="mb-3">
            <label>日付：</label>
            <p class="form-control-plaintext">{{ $attendance->date->format('Y年n月j日') }}</p>
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

        <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}">
            @csrf
            @method('PUT')

            <input type="hidden" name="date" value="{{ $attendance->date->toDateString() }}">
            <input type="hidden" name="user_id" value="{{ $attendance->user_id }}">

            <div class="mb-3">
                <label>出勤・退勤：</label>
                <div class="d-flex">
                    <input type="time" name="clock_in"
                        value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}"
                        class="form-control me-2">
                    ～
                    <input type="time" name="clock_out"
                        value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}"
                        class="form-control ms-2">
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
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
@endsection