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

        <!-- WebRTC call settings (public/js/sip-client.js): timeouts and the SIP debug trace; no secrets -->
        @auth
            <script>
                window.__SIP_OPTIONS__ = {
                    ringTimeout: {{ (int)config('services.sip.ring_timeout', 45) }},
                    audioWait:   {{ (int)config('services.sip.audio_wait', 5) }},
                    audioGrace:  {{ (int)config('services.sip.audio_grace', 10) }},
                    debug:       {{ config('services.sip.debug') ? 'true' : 'false' }},
                    debugUrl:    @json(config('services.sip.debug') ? route('dialerWebrtcDebug') : null),
                    csrf:        @json(config('services.sip.debug') ? csrf_token() : null),
                };
            </script>
        @endauth

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
                    domain   : @json(config('services.sip.domain')),
                    websocket: @json(config('services.sip.websocket')),
                };
            </script>
            <script type="module" src="{{ asset('js/webrtc-init.js') }}"></script>
        @endif

        <!-- Light / Dark switch (left of the bell): off = light (Original, the default), on = dark (Midnight Dark).
             Saved per user (users.theme); applied before the page draws (layouts/head). -->
        <div class="lg-theme-picker" id="lgThemePicker">
            <button type="button" class="lg-theme-toggle" id="lgThemeToggle" role="switch" aria-checked="false" aria-label="Dark mode" title="Dark mode">
                <svg class="lg-tt-icon lg-tt-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                <svg class="lg-tt-icon lg-tt-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <span class="lg-tt-knob"><svg class="lg-tt-sun-k" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg><svg class="lg-tt-moon-k" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
            </button>
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
