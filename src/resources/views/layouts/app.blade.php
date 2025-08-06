<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <script src="https://kit.fontawesome.com/66638ac94a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ asset('/css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/common.css') }}">
    @yield('css')
    @livewireStyles
</head>

<body>
    @yield('content')
    @yield('scripts')
    @livewireScripts
</body>

</html>