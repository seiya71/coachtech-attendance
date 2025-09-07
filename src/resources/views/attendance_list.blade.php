@extends('layouts.app')

@section('title', '勤怠一覧')

@section('content')
    <div class="container">
        <h1 class="text-xl font-bold mb-4">勤怠一覧</h1>

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('attendance.list', ['month' => $prevMonth->format('Y-m')]) }}" class="btn btn-secondary">
                ← 前月
            </a>

            <div class="text-lg font-semibold">
                {{ $currentMonth->format('Y/m') }}
            </div>

            <a href="{{ route('attendance.list', ['month' => $nextMonth->format('Y-m')]) }}" class="btn btn-secondary">
                翌月 →
            </a>
        </div>

        <table class="table-auto w-full border-collapse border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1">日付</th>
                    <th class="border px-2 py-1">出勤</th>
                    <th class="border px-2 py-1">退勤</th>
                    <th class="border px-2 py-1">休憩</th>
                    <th class="border px-2 py-1">合計</th>
                    <th class="border px-2 py-1">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendanceList as $day)
                    <tr>
                        <td class="border px-2 py-1">
                            {{ $day['date']->format('m/d（' . ['日', '月', '火', '水', '木', '金', '土'][$day['date']->dayOfWeek] . '）') }}
                        </td>
                        <td>{{ $day['clock_in'] }}</td>
                        <td>{{ $day['clock_out'] }}</td>
                        <td>{{ $day['break_time'] }}</td>
                        <td>{{ $day['work_time'] }}</td>
                        <td>
                            <a href="{{ route('attendance.detail', ['id' => $day['attendance_id'] ?? $day['date']->format('Y-m-d')]) }}"
                                class="text-blue-600 underline">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection