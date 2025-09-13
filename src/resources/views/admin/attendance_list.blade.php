@extends('admin.app')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
    <div class="container">
        <h1 class="text-xl font-bold mb-4">勤怠一覧</h1>

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('admin.attendance_list', ['date' => \Carbon\Carbon::parse($currentDate)->subDay()->toDateString()]) }}"
                class="btn btn-secondary">
                ← 前日
            </a>

            <div class="text-lg font-semibold">
                {{ \Carbon\Carbon::parse($currentDate)->format('Y年n月j日（' . ['日', '月', '火', '水', '木', '金', '土'][\Carbon\Carbon::parse($currentDate)->dayOfWeek] . '）') }}
            </div>

            <a href="{{ route('admin.attendance_list', ['date' => \Carbon\Carbon::parse($currentDate)->addDay()->toDateString()]) }}"
                class="btn btn-secondary">
                翌日 →
            </a>
        </div>

        <table class="table-auto w-full border-collapse border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1">名前</th>
                    <th class="border px-2 py-1">出勤</th>
                    <th class="border px-2 py-1">退勤</th>
                    <th class="border px-2 py-1">休憩</th>
                    <th class="border px-2 py-1">合計</th>
                    <th class="border px-2 py-1">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                                    <tr>
                                        <td class="border px-2 py-1">{{ $attendance->user->name }}</td>
                                        <td class="border px-2 py-1">{{ $attendance->clock_in_formatted }}</td>
                                        <td class="border px-2 py-1">{{ $attendance->clock_out_formatted }}</td>
                                        <td class="border px-2 py-1">{{ $attendance->break_time_formatted }}</td>
                                        <td class="border px-2 py-1">{{ $attendance->work_time_formatted }}</td>
                                        <td class="border px-2 py-1">
                                            <a href="{{ route('admin.attendance.detail', ['user_id' => $attendance->user_id,'attendance_id' => $attendance->id]) }}" class="text-blue-600 underline">
                                                詳細
                                            </a>
                                        </td>
                                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-2">この日の勤怠情報はありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection