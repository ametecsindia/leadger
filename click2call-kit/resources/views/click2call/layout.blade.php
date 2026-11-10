<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Click2Call')</title>
    <link rel="stylesheet" href="{{ asset('vendor/click2call/click2call.css') }}">
</head>
<body class="c2c-body">
    {{-- Standalone layout. To use your own: set C2C_LAYOUT to your layout; it needs @yield('content'),
         @stack('scripts') and the csrf-token meta tag, and must load vendor/click2call/click2call.css. --}}
    <nav class="c2c-nav">
        <a href="{{ route('c2c.dialer') }}">Click2Call</a>
        @can('c2c-admin')
            <a href="{{ route('c2c.vouchers') }}">Add Voucher</a>
            <a href="{{ route('c2c.agents') }}">Click2Call Users</a>
        @endcan
    </nav>
    <main class="c2c-main">
        @if(session('c2c_done'))<div class="c2c-alert c2c-alert-ok">{{ session('c2c_done') }}</div>@endif
        @if(session('c2c_error'))<div class="c2c-alert c2c-alert-error">{{ session('c2c_error') }}</div>@endif
        @if($errors->any())<div class="c2c-alert c2c-alert-error">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
