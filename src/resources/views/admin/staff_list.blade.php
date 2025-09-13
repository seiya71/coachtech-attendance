@extends('admin.app')

@section('title', 'スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
    <div class="container">
        <h1 class="text-xl font-bold mb-4">スタッフ一覧</h1>

        <table class="table-auto w-full border-collapse border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1">名前</th>
                    <th class="border px-2 py-1">メールアドレス</th>
                    <th class="border px-2 py-1">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staffs as $staff)
                    <tr>
                        <td class="border px-2 py-1">{{ $staff->name }}</td>
                        <td class="border px-2 py-1">{{ $staff->email }}</td>
                        <td class="border px-2 py-1">
                            <a href="{{ route('admin.attendance.staff', ['user_id' => $staff->id]) }}"
                                class="text-blue-600 underline">
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-2">スタッフが登録されていません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection