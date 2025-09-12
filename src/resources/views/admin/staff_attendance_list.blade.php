@extends('admin.app')

@section('title', $user->name . 'さんの勤怠')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff_attendance_list.css') }}">
@endsection

@section('content')
    <div class="container">
        <h1 class="text-xl font-bold mb-4">{{ $user->name }}さんの勤怠</h1>

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('admin.attendance.staff', ['user_id' => $user->id, 'month' => $prevMonth->format('Y-m')]) }}"
                class="btn btn-secondary">
                ← 前月
            </a>

            <div class="text-lg font-semibold">
                {{ $currentMonth->format('Y年/n月') }}
            </div>

            <a href="{{ route('admin.attendance.staff', ['user_id' => $user->id, 'month' => $nextMonth->format('Y-m')]) }}"
                class="btn btn-secondary">
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
                @forelse ($attendanceList as $day)
                    <tr>
                        <td class="border px-2 py-1">
                            {{ $day['date']->format('m/d（' . ['日', '月', '火', '水', '木', '金', '土'][$day['date']->dayOfWeek] . '）') }}
                        </td>
                        <td class="border px-2 py-1">{{ $day['clock_in'] ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $day['clock_out'] ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $day['break_time'] ?? '00:00' }}</td>
                        <td class="border px-2 py-1">{{ $day['work_time'] ?? '00:00' }}</td>
                        <td class="border px-2 py-1">
                            @if($day['attendance_id'])
                                                    <a href="{{ route('admin.attendance.detail', [
                                    'user_id' => $user->id,
                                    'attendance_id' => $day['attendance_id']
                                ]) }}">
                                                        詳細
                                                    </a>
                            @else
                                                    <a href="{{ route('admin.attendance.detail.new', [
                                    'user_id' => $user->id,
                                    'date' => $day['date']->toDateString()
                                ]) }}">
                                                        詳細
                                                    </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-2">この月の勤怠情報はありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection