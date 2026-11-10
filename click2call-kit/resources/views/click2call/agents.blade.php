@extends(config('click2call.layout'))
@section('title', 'Click2Call Users')

@section('content')
<div class="c2c-card">
    <h2>Click2Call Users</h2>
    <p class="c2c-muted">Switch Click2Call on for a user and enter the user's own phone: the provider rings it first, then the customer.
        @if($limit > 0) Licensed users: {{ $inUse }} / {{ $limit }}. @endif</p>
    <div class="c2c-table-wrap">
        <table class="c2c-table">
            <thead><tr><th>User</th><th>Click2Call</th><th>Agent number</th><th>Default VMN</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                @php $f = 'c2c-agent-' . $u->getKey(); @endphp
                <tr>
                    <td><b>{{ $u->name }}</b><br><span class="c2c-muted">{{ $u->email }}</span></td>
                    <td>
                        <select name="enabled" form="{{ $f }}" class="c2c-select" style="width:auto">
                            <option value="1" @selected($u->c2c_channel)>On</option>
                            <option value="0" @selected(!$u->c2c_channel)>Off</option>
                        </select>
                    </td>
                    <td><input name="click_agent_number" form="{{ $f }}" class="c2c-input" value="{{ $u->click_agent_number }}" placeholder="10-digit mobile" inputmode="numeric"></td>
                    <td><input name="c2c_default_number" form="{{ $f }}" class="c2c-input" value="{{ $u->c2c_default_number }}" placeholder="optional"></td>
                    <td>
                        <form id="{{ $f }}" method="POST" action="{{ route('c2c.agents.update', $u) }}">@csrf</form>
                        <button class="c2c-btn c2c-btn-sm" type="submit" form="{{ $f }}">Save</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
