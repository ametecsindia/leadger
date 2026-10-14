<?php
$webmailsAlerts = Helper::webmailsAlerts();
$eventsAlerts = Helper::eventsAlerts();
$alerts = count($webmailsAlerts) + count($eventsAlerts);
$webmailsNewCount = Helper::webmailsNewCount();

$user = Auth::user();
$userName = ($user && !empty($user->name)) ? $user->name : 'Sales Admin';
$userInitials = 'SA';
if ($user && !empty($user->name)) {
    $words = preg_split('/\s+/', trim($user->name));
    if (count($words) >= 2) {
        $userInitials = strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
    } else {
        $userInitials = strtoupper(substr($user->name, 0, 2));
    }
}
$headerCampaigns = \App\Models\Campaign::where('status', 1)->orWhereNull('status')->orderBy('name')->get();
$headerCampaignCount = $headerCampaigns->count();
?>

<header class="top-header app-header" style="background: #0F172A; height: 60px; min-height: 60px; max-height: 60px; display: flex; align-items: center; justify-content: flex-end; padding: 0 20px; border-bottom: 1px solid rgba(255,255,255,0.08); position: fixed; top: 0; right: 0; left: 240px; width: calc(100% - 240px); z-index: 1040; box-sizing: border-box; overflow: visible;">
    <div></div>

    <!-- Header Actions Container -->
    <div class="header-actions" style="display: flex; align-items: center; gap: 14px; height: 100%; flex-wrap: nowrap; flex-shrink: 0;">
        <!-- 1. Active Call Workspace CTA Button -->
        <a href="{{ route('dialer') }}" class="header-active-call-btn" style="height: 38px; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; border-radius: 8px; background: var(--lg-accent); border: 1px solid var(--lg-accent); padding: 0 16px; font-size: 13.5px; font-weight: 600; color: #FFFFFF; text-decoration: none; box-shadow: 0 2px 8px rgba(37,99,235,0.35); transition: all 0.15s ease; flex-shrink: 0; box-sizing: border-box;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <span>Active Call Workspace</span>
        </a>

        <!-- WebRTC Telephony Authentication Controls (if configured) -->
        @if(auth()->user() && auth()->user()->smartping_user_id && auth()->user()->smartping_password)
            @php
                $autoRegisterSip = 'true';
            @endphp
            <script>
                window.__SIP_AUTO_REGISTER__ = {{ $autoRegisterSip ? 'true' : 'false' }};
                window.__SIP_CONFIG__ = {
                    username : @json(auth()->user()->smartping_user_id),
                    password : @json(auth()->user()->smartping_password),
                    domain   : @json(env('SIP_DOMAIN',    'voip.ametecsindia.com')),
                    websocket: @json(env('SIP_WEBSOCKET', 'wss://voip.ametecsindia.com:7443')),
                };
            </script>
            <script type="module" src="{{ asset('js/webrtc-init.js') }}"></script>
        @endif

        <!-- Theme picker (left of the bell): Original (default) / Executive Slate / Professional Green / Midnight Dark -->
        <div class="lg-theme-picker" id="lgThemePicker">
            <button type="button" class="lg-theme-btn" id="lgThemeBtn" title="Theme" aria-label="Theme" aria-haspopup="true" aria-expanded="false" aria-controls="lgThemeMenu">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
            </button>
            <div class="lg-theme-menu" id="lgThemeMenu" role="menu" aria-label="Theme" hidden>
                <div class="lg-theme-menu-title">Theme</div>
                @foreach([
                    'original' => ['Original', ['#0F172A', '#1464F4', '#F7F8FA'], true],
                    'slate' => ['Executive Slate', ['#2B3340', '#4A6FA5', '#F5F6F8'], false],
                    'green' => ['Professional Green', ['#0F3D2E', '#12805A', '#F2F8F5'], false],
                    'dark' => ['Midnight Dark', ['#070D1C', '#3B82F6', '#0F172A'], false],
                ] as $themeKey => [$themeName, $themeDots, $themeDefault])
                    <button type="button" class="lg-theme-opt" role="menuitemradio" aria-checked="false" data-theme-value="{{ $themeKey }}" tabindex="-1">
                        <span class="lg-theme-dots" aria-hidden="true">@foreach($themeDots as $dot)<i style="background: {{ $dot }};"></i>@endforeach</span>
                        <span class="lg-theme-name">{{ $themeName }}</span>
                        @if($themeDefault)<span class="lg-theme-default">Default</span>@endif
                        <svg class="lg-theme-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                @endforeach
                <button type="button" class="lg-theme-reset" id="lgThemeReset" tabindex="-1">Reset to default</button>
            </div>
        </div>

        <!-- 2. Standalone Notification Bell Button -->
        <div class="dropdown" style="position: relative; display: flex; align-items: center; height: 100%; flex-shrink: 0;">
            <a href="javascript:void(0)" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="headerNotificationBell" aria-label="Notifications" style="height: 38px; width: 38px; min-width: 38px; min-height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: #FFFFFF; position: relative; text-decoration: none; cursor: pointer; transition: background 0.15s ease; flex-shrink: 0; box-sizing: border-box;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                @if(!empty($alerts) && $alerts > 0)
                    <span style="position: absolute; top: 6px; right: 6px; width: 8px; height: 8px; border-radius: 50%; background: #F59E0B; border: 2px solid #0F172A;"></span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-right animated fadeInUp" aria-labelledby="headerNotificationBell" style="right: 0; left: auto; top: calc(100% + 8px); width: 320px; z-index: 1060; padding: 0; border: none; background: transparent; box-shadow: none;">
                <div style="background: #0B1E40; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12); box-shadow: 0 12px 30px rgba(0,0,0,0.5); overflow: hidden;">
                    <div class="p-2 border-bottom d-flex justify-content-between align-items-center" style="border-color: rgba(255,255,255,0.08) !important; padding: 12px 16px;">
                        <span class="font-weight-bold text-white" style="font-size: 13px;">Notifications</span>
                        <span class="badge" style="font-size: 11px; background-color: var(--lg-accent); color: #fff; border-radius: 100px; padding: 3px 8px;">{{ $alerts }} New</span>
                    </div>
                    <ul class="list-group list-group-gap m-a-0" style="padding: 6px 12px; max-height: 280px; overflow-y: auto;">
                        @forelse($webmailsAlerts as $webmailsAlert)
                            <li class="list-group-item lt box-shadow-z0 b" style="background: rgba(255,255,255,0.05); color: #fff; border-color: rgba(255,255,255,0.1); margin-bottom: 4px; border-radius: 6px; padding: 8px 12px;">
                                <span class="clear block">
                                    <small class="text-muted">{{ $webmailsAlert->from_name }}</small><br>
                                    <a href="{{ route('webmailsEdit', ['id' => $webmailsAlert->id]) }}" style="color: #60A5FA; font-weight: bold; font-size: 12.5px;">{{ $webmailsAlert->title }}</a>
                                    <br>
                                    <small class="text-muted">{{ date('d M Y  h:i A', strtotime($webmailsAlert->date)) }}</small>
                                </span>
                            </li>
                        @empty
                            <li class="p-3 text-center text-muted" style="font-size: 12px; color: rgba(255,255,255,0.6) !important;">No new notifications</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- 3. Admin Profile Badge: [ SA ] Sales Admin Command Center View [ ▼ ] -->
        <div class="dropdown" style="position: relative; display: flex; align-items: center; height: 100%; flex-shrink: 0;" id="userProfileDropdownWrap">
            <a href="javascript:void(0)" id="headerUserProfile" style="display: flex; align-items: center; gap: 10px; padding: 4px 8px; border-radius: 8px; cursor: pointer; color: #FFFFFF; text-decoration: none; background: transparent; transition: background 0.15s ease; flex-shrink: 0; box-sizing: border-box; white-space: nowrap;">
                <span style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--lg-accent); font-size: 13px; font-weight: 700; color: #FFFFFF; flex-shrink: 0; box-shadow: 0 0 0 2px rgba(255,255,255,0.15);">
                    {{ $userInitials }}
                </span>
                <div style="display: flex; flex-direction: column; justify-content: center; text-align: left; line-height: 1.2; flex-shrink: 0;">
                    <span style="font-size: 13.5px; font-weight: 700; color: #FFFFFF; white-space: nowrap;">{{ $userName }}</span>
                    <span style="font-size: 11px; color: #94A3B8; font-weight: 500; white-space: nowrap;">Command Center View</span>
                </div>
                <!-- Visible downward chevron "▼" -->
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8; margin-left: 2px; flex-shrink: 0; transition: transform 0.2s ease;"><path d="m6 9 6 6 6-6"/></svg>
            </a>

            <!-- Clean White Dropdown Menu positioned directly below the Admin profile -->
            <div class="dropdown-menu dropdown-menu-right animated fadeInDown lg-dropdown-menu" id="headerUserProfileMenu" aria-labelledby="headerUserProfile" style="right: 0; left: auto; top: calc(100% + 8px); min-width: 200px; z-index: 1060; display: none;">
                
                <!-- Profile -->
                <a class="dropdown-item lg-dropdown-item" href="{{ route('usersEdit', Auth::user()->id ?? 1) }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 10px; color: #64748B;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Profile</span>
                </a>

                <!-- Inbox -->
                <a class="dropdown-item lg-dropdown-item" href="{{ route('webmails') }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 10px; color: #64748B;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <span>Inbox</span>
                    @if(@$webmailsNewCount > 0)
                        <span class="badge ml-auto" style="font-size: 10px; background: #EF4444; color: #fff; padding: 3px 7px; border-radius: 100px; margin-left: auto;">{{ @$webmailsNewCount }}</span>
                    @endif
                </a>

                <div class="dropdown-divider" style="border-color: #F1F5F9; margin: 4px 0;"></div>

                <!-- Logout -->
                <a class="dropdown-item lg-dropdown-item-danger" id="logout-link" href="{{ url('/logout') }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 10px; color: #EF4444;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>

    </div>
</header>

<!-- Hidden Logout Form -->
<form id="auto-logout-form" action="{{ route('logout') }}" method="GET" style="display: none;">
    @csrf
</form>

<div id="webrtc-container" style="margin-top:15px; display:none;">
    <iframe id="webrtc-frame" width="100%" height="600px" frameborder="0"></iframe>
</div>

<div id="sso-container" 
     style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
            width:400px; height:300px; background:#fff; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:9999;">
    <iframe id="sso-iframe" allow="microphone; camera" sandbox="allow-scripts allow-same-origin"
            style="width:100%; height:100%; border:none;"></iframe>
</div>

<script>
    let sessionTimeout = {{ config('session.lifetime') * 60 * 1000 }}; // milliseconds
    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            document.getElementById('auto-logout-form').submit();
        }, sessionTimeout);
    }

    ['click', 'mousemove', 'keypress', 'scroll'].forEach(event => {
        window.addEventListener(event, resetTimer);
    });

    resetTimer();

    // User Profile Dropdown Toggle Logic
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('headerUserProfile');
        const profileMenu = document.getElementById('headerUserProfileMenu');

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = (profileMenu.style.display === 'none' || !profileMenu.classList.contains('show'));
                if (isHidden) {
                    profileMenu.style.display = 'block';
                    profileMenu.classList.add('show');
                } else {
                    profileMenu.style.display = 'none';
                    profileMenu.classList.remove('show');
                }
            });

            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.style.display = 'none';
                    profileMenu.classList.remove('show');
                }
            });
        }
    });
</script>
