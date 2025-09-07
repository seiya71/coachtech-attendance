@extends('layouts.app')

@section('title', '勤怠詳細')

@section('content')
    <div class="container">
        <h1>勤怠詳細</h1>

        <form method="POST" action="{{ route('attendances.update', $attendance->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>名前：</label>
                <input type="text" name="user_name" value="{{ $attendance->user->name }}" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label>日付：</label>
                <input type="text" name="date" value="{{ $attendance->date->format('Y年n月j日') }}" class="form-control"
                    readonly>
            </div>

            <div class="mb-3">
                <label>出勤・退勤：</label>
                <div class="d-flex">
                    <input type="time" name="clock_in" value="{{ $attendance->clock_in }}" class="form-control me-2" {{ $attendance->is_editable ? '' : 'readonly' }}>
                    ～
                    <input type="time" name="clock_out" value="{{ $attendance->clock_out }}" class="form-control ms-2" {{ $attendance->is_editable ? '' : 'readonly' }}>
                </div>
            </div>

            <div class="mb-3">
                <label>休憩：</label>
                @foreach($attendance->breaks as $index => $break)
                    <div class="d-flex mb-1">
                        <input type="time" name="breaks[{{ $index }}][start]" value="{{ $break->start }}"
                            class="form-control me-2" {{ $attendance->is_editable ? '' : 'readonly' }}>
                        ～
                        <input type="time" name="breaks[{{ $index }}][end]" value="{{ $break->end }}" class="form-control ms-2"
                            {{ $attendance->is_editable ? '' : 'readonly' }}>
                    </div>
                @endforeach

                @if($attendance->is_editable)
                    <div class="d-flex">
                        <input type="time" name="breaks[new][start]" class="form-control me-2">
                        ～
                        <input type="time" name="breaks[new][end]" class="form-control ms-2">
                    </div>
                @endif
            </div>

            <div class="mb-3">
                <label>備考：</label>
                @if($attendance->is_editable)
                    <textarea name="note" class="form-control">{{ $attendance->note }}</textarea>
                @else
                    <p class="form-control-plaintext">{{ $attendance->note }}</p>
                @endif
            </div>

            <div class="mb-3">
                @if($attendance->is_editable)
                    <button type="submit" class="btn btn-primary">修正</button>
                @else
                    <div class="alert alert-info">申請中です</div>
                @endif
            </div>
        </form>
    </div>
@endsection