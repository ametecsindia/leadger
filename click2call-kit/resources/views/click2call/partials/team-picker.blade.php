{{-- Teams: a multi-select of config('click2call.team_model') rows, or team ids typed in when there is no team model --}}
@if($teams->isNotEmpty())
    <select name="{{ $name }}" class="c2c-select" multiple size="3" style="height:auto">
        @foreach($teams as $team)
            <option value="{{ $team->id }}" @selected(in_array($team->id, $selected))>{{ $team->name }}</option>
        @endforeach
    </select>
@else
    <input class="c2c-input" placeholder="Team ids, e.g. 1, 8" data-team-ids="{{ $name }}"
           oninput="(function(el){var f=el.form;f.querySelectorAll('input[data-team-id]').forEach(function(x){x.remove()});el.value.split(',').map(function(s){return s.trim()}).filter(function(s){return /^\d+$/.test(s)}).forEach(function(id){var h=document.createElement('input');h.type='hidden';h.name='{{ $name }}';h.value=id;h.setAttribute('data-team-id','1');f.appendChild(h)})})(this)">
@endif
