<header class="header">
    <div class="header__logo">
        <a href="{{ route('admin.attendance.list') }}" class="logo-link"><img src="{{ asset('images/logo.png') }}" class="logo-item" alt="logo"></a>
    </div>

    @if(Auth::check())
    @php
    $currentPath = request()->path();
    $isAuthPage = in_array($currentPath, ['admin/login']);
    @endphp

    @if(!$isAuthPage)
    <nav class="header__nav">
        <ul class="nav-menu">
            <li class="nav-item"><a href="{{ route('admin.attendances') }}" class="nav-link">勤怠一覧</a></li>
            <li class="nav-item"><a href="{{ route('admin.users') }}" class="nav-link">スタッフ一覧</a></li>
            <li class="nav-item"><a href="{{ route('admin.requests">申請一覧</a></li>
            <li class="nav-item">
                <form action="/logout" method="POST">
                    @csrf
                    <button class="button__logout" type="submit">ログアウト</button>
                </form>
            </li>
        </ul>
    </nav>
    @endif
    @endif
</header>