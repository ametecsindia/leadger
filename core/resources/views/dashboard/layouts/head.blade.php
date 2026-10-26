<meta charset="utf-8"/>
{{-- Admin color theme, set before any CSS loads so the page never flashes the wrong theme.
     The user's saved theme (server) wins; a choice not yet saved (offline / failed request) is kept locally until it is.
     Signed-out pages (login) always use the original look. Never follows the computer's dark mode. --}}
<script>
(function () {
    var allowed = ['original', 'slate', 'green', 'dark'];
    var t = @json(auth()->check() ? (auth()->user()->theme ?: 'original') : null);
    if (t !== null) {
        try {
            var pending = localStorage.getItem('lg_theme_pending');
            if (pending && allowed.indexOf(pending) !== -1) t = pending;
            else localStorage.setItem('lg_theme', t);
        } catch (e) {}
    }
    if (allowed.indexOf(t) === -1) t = 'original';
    var html = document.documentElement;
    html.setAttribute('data-theme', t);
    html.style.colorScheme = t === 'dark' ? 'dark' : 'light';
})();
</script>
<title>@yield('title')</title>
<meta name="description" content="{{ Helper::GeneralSiteSettings("site_desc_".@Helper::currentLanguage()->code) }}"/>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-barstyle" content="black-translucent">
<link rel="apple-touch-icon" href="{{ asset('assets/dashboard/images/logo.png') }}">
<meta name="apple-mobile-web-app-title" content="LeadGER">
<base href="{{ route("adminHome") }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<meta name="mobile-web-app-capable" content="yes">
<link rel="shortcut icon" sizes="196x196" href="{{ asset('assets/dashboard/images/logo.png') }}">
@stack('before-styles')
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/animate.css/animate.min.css') }}" type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/animate.css/animate.min.css') }}" type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/glyphicons/glyphicons.css') }}" type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/font-awesome/css/font-awesome.min.css') }}"
      type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/fonts/material-design-icons/material-design-icons.css') }}"
      type="text/css"/>
      <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"> -->
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/bootstrap/dist/css/bootstrap.min.css') }}"
      type="text/css"/>
      <link rel="stylesheet" href="{{ asset('assets/dashboard/js/select2/dist/css/select2.min.css') }}"
      type="text/css"/>
      <link rel="stylesheet" href="{{ asset('assets/dashboard/css/toastr/toastr.min.css') }}"
      type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/app.css') }}" type="text/css"/>

<!-- <link rel="stylesheet" href="{{ asset('assets/dashboard/css/font.css') }}" type="text/css"/> -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/topic.css') }}" type="text/css"/>

<link rel="stylesheet" href="{{ asset('assets/dashboard/css/custom.css') }}" type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/leadger-theme.css') }}?v=1.9" type="text/css"/>
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-design-system.css') }}?v=5" type="text/css"/>

@if( @Helper::currentLanguage()->direction=="rtl")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/css/bootstrap-rtl/dist/bootstrap-rtl.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/css/app.rtl.css') }}">
@endif
@stack('after-styles')
{{-- Executive Slate / Professional Green / Midnight Dark (inactive for the original theme). Loaded last so it wins. --}}
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-themes.css') }}?v=4" type="text/css"/>
<script src="{{ asset('assets/dashboard/js/lg-themes.js') }}?v=3" defer></script>{{-- Role permissions for page scripts: lgCan('reports.call_report.export'); controls marked data-lg-perm="<key>"
     are hidden when the role lacks the key. The server checks every request again. --}}
@php($lgPerms = \App\Support\RoleAccess::forScript(Auth::user()))
@if(Auth::check() && !$lgPerms['full'])
{{-- the keys come from config/permissions.php only, so they are printed unescaped --}}
<style>{!! implode(',', array_map(fn ($k) => '[data-lg-perm="' . $k . '"]', array_diff(array_keys(\App\Support\RoleAccess::keys()), $lgPerms['keys']))) ?: '[data-lg-perm-none]' !!}{display:none !important}</style>
@endif
<script>
    window.LG_PERMS = @json($lgPerms);
    window.lgCan = function (key) { return !!(window.LG_PERMS && (window.LG_PERMS.full || window.LG_PERMS.keys.indexOf(key) !== -1)); };
</script>
