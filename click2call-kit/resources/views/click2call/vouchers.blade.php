@extends(config('click2call.layout'))
@section('title', 'Add Voucher')

@section('content')
<div class="c2c-card">
    <h2>Add Voucher</h2>
    <p class="c2c-muted">Enter the voucher code from Ametecs. Its numbers <b>replace</b> the current ones (the old list is kept in the history); then assign each number to teams.</p>
    <form method="POST" action="{{ route('c2c.vouchers.add') }}" class="c2c-row">
        @csrf
        <div class="c2c-field">
            <label for="voucher_code">Voucher code</label>
            <input id="voucher_code" name="voucher_code" class="c2c-input" required maxlength="100" autocomplete="off" value="{{ old('voucher_code') }}">
        </div>
        <div class="c2c-field" style="flex:0 0 auto"><button class="c2c-btn" type="submit">Add Voucher</button></div>
    </form>
    @if($vouchers->isNotEmpty())
        <p class="c2c-muted">Last voucher: {{ $vouchers->first()->voucher_code }} &middot; {{ $vouchers->first()->numbers }} number(s)
            &middot; expires {{ optional($vouchers->first()->expires_at)->format('d M Y') ?? '-' }} &middot; added {{ $vouchers->first()->created_at->format('d M Y H:i') }}</p>
    @endif
</div>

<div class="c2c-card">
    <h2>Numbers (VMN)</h2>
    <form method="POST" action="{{ route('c2c.vmns.bulk') }}" id="c2c-bulk">
        @csrf
        <div class="c2c-row" style="margin-bottom:12px">
            <div class="c2c-field">
                <label>Teams for the selected numbers</label>
                @include('click2call.partials.team-picker', ['name' => 'team_ids[]', 'selected' => []])
            </div>
            <div class="c2c-field">
                <label for="bulk-usage">Usage</label>
                <select id="bulk-usage" name="usage" class="c2c-select">
                    <option value="">Keep</option><option value="click2call">Click2Call</option><option value="obd">OBD</option>
                    <option value="both">Click2Call + OBD</option><option value="webrtc">WebRTC on</option>
                </select>
            </div>
            <div class="c2c-field" style="flex:0 0 auto">
                <button class="c2c-btn" name="action" value="update" type="submit">Update selected</button>
                <button class="c2c-btn c2c-btn-danger" name="action" value="delete" type="submit" onclick="return confirm('Delete the selected numbers?')">Delete</button>
            </div>
        </div>
        <div class="c2c-table-wrap">
            <table class="c2c-table">
                <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.c2c-pick').forEach(c => c.checked = this.checked)"></th>
                    <th>Number</th><th>Click2Call</th><th>WebRTC</th><th>OBD</th><th>Verified</th><th>Teams</th><th>Expiry</th></tr></thead>
                <tbody>
                @forelse($vmns as $v)
                    <tr>
                        <td><input type="checkbox" class="c2c-pick" name="ids[]" value="{{ $v->id }}"></td>
                        <td><b>+91 {{ $v->virtual_number }}</b></td>
                        <td><span class="c2c-badge {{ $v->click2call ? 'on' : '' }}">{{ $v->click2call ? 'Yes' : 'No' }}</span></td>
                        <td><span class="c2c-badge {{ $v->webrtc ? 'on' : '' }}">{{ $v->webrtc ? 'Yes' : 'No' }}</span></td>
                        <td><span class="c2c-badge {{ $v->obd ? 'on' : '' }}">{{ $v->obd ? 'Yes' : 'No' }}</span></td>
                        <td>{{ $v->tc_verified ? 'Yes' : 'No' }}</td>
                        <td>
                            @php $names = $teams->whereIn('id', $v->team_ids ?? [])->pluck('name')->implode(', '); @endphp
                            {{ $names !== '' ? $names : (empty($v->team_ids) ? 'Not assigned (admins only)' : implode(', ', $v->team_ids)) }}
                        </td>
                        <td>
                            @if($v->expires_at)
                                <span class="c2c-badge {{ $v->isAvailable() ? 'on' : 'off' }}">{{ $v->expires_at->format('d M Y') }}</span>
                            @else - @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="c2c-muted">No numbers yet. Add a voucher above.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>

@if($smartGroups->isNotEmpty())
<div class="c2c-card">
    <h2>Smart31 / 1600 numbers (from the voucher)</h2>
    <table class="c2c-table">
        <thead><tr><th>Group</th><th>Numbers</th><th>Last usage date</th></tr></thead>
        <tbody>@foreach($smartGroups as $g)<tr><td>{{ $g->group_name ?: '-' }}</td><td>{{ $g->numbers }}</td><td>{{ $g->last_usage }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endif
@endsection
