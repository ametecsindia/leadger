@extends(config('click2call.layout'))
@section('title', 'Click2Call')

@section('content')
<div class="c2c-card" id="c2c-dialer"
     data-start-url="{{ route('c2c.start') }}"
     data-status-url="{{ url('click2call/call/__ID__/status') }}"
     data-outcome-url="{{ url('click2call/call/__ID__/outcome') }}"
     data-pool-url="{{ url('click2call/pool/__POOL__') }}"
     data-poll-seconds="{{ config('click2call.poll_seconds') }}"
     data-poll-max-minutes="{{ config('click2call.poll_max_minutes') }}">
    <h2>Make a Call</h2>

    @unless($canCall)
        <div class="c2c-alert c2c-alert-error">Click2Call is not enabled for your account, or your agent number is missing. Ask an admin.</div>
    @endunless

    <div class="c2c-row">
        <div class="c2c-field">
            <label for="c2c-mobile">Customer number</label>
            <input id="c2c-mobile" class="c2c-input" type="tel" inputmode="numeric" maxlength="20" autocomplete="off"
                   placeholder="10-digit mobile number" value="{{ $number }}">
        </div>
        <div class="c2c-field">
            <label for="c2c-vmn">Call from (VMN)</label>
            <select id="c2c-vmn" class="c2c-select">
                @forelse($vmns as $v)
                    <option value="{{ $v->virtual_number }}" data-available="{{ $v->available ? 1 : 0 }}"
                            @selected($defaultVmn === $v->virtual_number) @disabled(!$v->available)>
                        {{ $v->display }}{{ $v->available ? '' : ' (expired ' . $v->expiry_label . ')' }}
                    </option>
                @empty
                    <option value="">No Click2Call numbers available</option>
                @endforelse
            </select>
        </div>
    </div>

    <div class="c2c-field">
        <span class="c2c-muted">Number type:</span>
        <label class="c2c-check"><input type="radio" name="c2c-pool" value="" checked> Voucher VMN</label>
        <label class="c2c-check"><input type="radio" name="c2c-pool" value="smart31"> Smart31</label>
        <label class="c2c-check"><input type="radio" name="c2c-pool" value="1600"> Enabled 1600</label>
    </div>

    <button id="c2c-call" type="button" class="c2c-btn c2c-btn-call" disabled>Click2Call</button>
    <div id="c2c-status" class="c2c-status" hidden aria-live="polite"><span id="c2c-status-text"></span><span id="c2c-timer"></span></div>

    <form id="c2c-outcome" hidden style="margin-top:18px">
        <div class="c2c-row">
            <div class="c2c-field">
                <label for="c2c-outcome-value">Call outcome</label>
                <select id="c2c-outcome-value" class="c2c-select" required>
                    <option value="">Select</option>
                    <option>Interested</option><option>Not interested</option><option>Call back</option>
                    <option>Wrong number</option><option>Not reachable</option>
                </select>
            </div>
            <div class="c2c-field">
                <label for="c2c-follow-up">Follow-up (optional)</label>
                <input id="c2c-follow-up" class="c2c-input" type="datetime-local">
            </div>
        </div>
        <div class="c2c-field">
            <label for="c2c-notes">Feedback</label>
            <input id="c2c-notes" class="c2c-input" maxlength="500">
        </div>
        <button class="c2c-btn" type="submit">Save outcome</button>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/click2call/click2call.js') }}"></script>
@endpush
