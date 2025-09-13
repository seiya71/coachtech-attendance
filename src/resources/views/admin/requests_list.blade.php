@extends('admin.app')

@section('title', '申請一覧')

@section('content')
    <div class="container">
        <h1>申請一覧</h1>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ !$isApproved ? 'active' : '' }}" href="{{ route('admin.applications.list') }}">
                    承認待ち
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $isApproved ? 'active' : '' }}"
                    href="{{ route('admin.applications.list.approved') }}">
                    承認済み
                </a>
            </li>
        </ul>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>{{ $app->status_label }}</td>
                        <td>{{ $app->user->name }}</td>
                        <td>{{ $app->date->format('Y/m/d') }}</td>
                        <td>{{ $app->reason }}</td>
                        <td>{{ $app->created_at->format('Y/m/d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.application.approve.form', $app->id) }}">
                                詳細
                            </a>

                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">申請はありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection