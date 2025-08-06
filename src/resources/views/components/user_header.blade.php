<header class="header">
    <div class="header__logo">
        @if(Auth::check() && !Auth::user()->hasVerifiedEmail())
        <a href="#" class="logo-link" onclick="event.preventDefault();"><img src="{{ asset('images/logo.png') }}" class="logo-item" alt="logo"></a>
        @else
        <a href="{{ route('attendance.list') }}" class="logo-link"><img src="{{ asset('images/logo.png') }}" class="logo-item" alt="logo"></a>
        @endif
    </div>

    @if(Auth::check())
    @php
    $currentPath = request()->path();
    $isAuthPage = in_array($currentPath, ['register', 'login', 'email/verify']);
    @endphp

    @if(!$isAuthPage)
    <nav class="header__nav">
        <ul class="nav-menu">
            @if($currentPath === 'attendance' && isset($attendanceStatus) && $attendanceStatus === 'after_work')
            {{--nav for after work--}}
            <li class=nav-item><a href="{{ route('attendance.list') }}" class="nav-link">今月の出勤一覧</a></li>
            <li class="nav-item"><a href="{{ route('stamp_correction_request.list') }}" class="nav-link">申請一覧</a></li>
            @else
            {{--nav for default--}}
            <li class="nav-item"><a href="{{ route('attendance.create') }}" class="nav-link">勤怠</a></li>
            <li class="nav-item"><a href="{{ route('attendance.list') }}" class="nav-link">勤怠一覧</a></li>
            <li class="nav-item"><a href="{{ route('stamp_correction_request.index') }}" class="nav-link">申請</a></li>
            @endif
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" class="nav-item__form">
                    @csrf
                    <button class="button__logout" type="submit">ログアウト</button>
                </form>
            </li>
        </ul>
    </nav>
    @endif
    @endif
</header>