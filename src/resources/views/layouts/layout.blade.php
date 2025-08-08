<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>coachtech-attendance</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    @yield('css')
</head>
<body>
    <header>
        <div class="logo">
            <img class="logo-image" src="{{ asset('images/icons/logo.svg') }}" alt="coachtech icon">
        </div>
        @auth
            @if(Auth::user()->role === 'admin')
                <nav class="header-nav">
                    <a class="nav-link" href="">勤怠一覧</a>
                    <a class="nav-link" href="">スタッフ一覧</a>
                    <a class="nav-link" href="">申請一覧</a>
                    <form class="nav-link" action="">
                        <button class="nav-logout">ログアウト</button>
                    </form>
                </nav>
            @else
                <nav class="header-nav">
                    <a class="nav-link" href="">勤怠</a>
                    <a class="nav-link" href="">勤怠一覧</a>
                    <a class="nav-link" href="">申請</a>
                    <form class="nav-link" action="">
                        <button class="nav-logout">ログアウト</button>
                    </form>
                </nav>
            @endif
        @endauth
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>