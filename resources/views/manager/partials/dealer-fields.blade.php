<div class="ef-cols3">
    <div class="ef-field"><label>Ragione sociale</label><input class="ef-input" name="business_name" value="{{ $dealer?->business_name }}" required></div>
    <div class="ef-field"><label>Codice nel report (Id Dealer)</label><input class="ef-input" name="external_code" value="{{ $dealer?->external_code }}"></div>
    <div class="ef-field"><label>Agente</label><select class="ef-input" name="agent_id"><option value="">—</option>@foreach ($agents as $a)<option value="{{ $a->id }}" @selected($dealer?->agent_id === $a->id)>{{ $a->user->name }}</option>@endforeach</select></div>
    <div class="ef-field"><label>Area</label><select class="ef-input" name="area_id"><option value="">—</option>@foreach ($areas as $a)<option value="{{ $a->id }}" @selected($dealer?->area_id === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
    <div class="ef-field"><label>Ambassador</label><select class="ef-input" name="ambassador_id"><option value="">—</option>@foreach ($ambassadors as $a)<option value="{{ $a->id }}" @selected($dealer?->ambassador_id === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
    <div class="ef-field"><label>Regione</label><input class="ef-input" name="region" value="{{ $dealer?->region }}"></div>
    <div class="ef-field"><label>Comune</label><input class="ef-input" name="municipality" value="{{ $dealer?->municipality }}"></div>
    <div class="ef-field"><label>Email</label><input class="ef-input" type="email" name="email" value="{{ $dealer?->email }}"></div>
    <div class="ef-field"><label>Telefono</label><input class="ef-input" name="phone" value="{{ $dealer?->phone }}"></div>
</div>
