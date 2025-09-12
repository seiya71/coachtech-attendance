<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>coachtech-attendance</title>
    <link rel="stylesheet" href="{{ asset('css/admin/app.css') }}" />
    @yield('css')
</head>

<body>
    <header>
        <div class="logo">
            <img class="logo-image" src="{{ asset('images/icons/logo.svg') }}" alt="coachtech icon">
        </div>
        @auth
            <nav class="header-nav">
                <a class="nav-link" href="/admin/attendance/list">勤怠一覧</a>
                <a class="nav-link" href="/admin/staff/list">スタッフ一覧</a>
                <a class="nav-link" href="/stamp_correction_request/list">申請一覧</a>
                <form class="nav-link" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-logout" type="submit">ログアウト</button>
                </form>
            </nav>
        @endauth
    </header>
    <main>
        @yield('content')
    </main>
</body>

</html>