{{-- Filtro periodo: mese, trimestre, anno o intervallo personalizzato. Variabili: $range, $action, $extra (parametri da mantenere). --}}
@php($extra = $extra ?? [])
<form class="ef-card" method="get" action="{{ $action }}" data-date-filter>
    @foreach ($extra as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <div class="ef-cols4" style="align-items:end">
        <div class="ef-field" style="margin:0">
            <label for="period">Periodo</label>
            <select class="ef-input" id="period" name="period">
                @foreach (\App\Services\DateRange::PERIODS as $key => $label)<option value="{{ $key }}" @selected($range->period === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="ef-field" style="margin:0" data-for="month">
            <label for="month">Mese</label>
            <input class="ef-input" id="month" type="month" name="month" value="{{ $range->input['month'] ?? $range->from->format('Y-m') }}">
        </div>
        <div class="ef-field" style="margin:0" data-for="quarter">
            <label for="quarter">Trimestre</label>
            <select class="ef-input" id="quarter" name="quarter">
                @foreach ([1, 2, 3, 4] as $q)<option value="{{ $q }}" @selected(($range->input['quarter'] ?? $range->from->quarter) === $q)>{{ $q }}° trimestre</option>@endforeach
            </select>
        </div>
        <div class="ef-field" style="margin:0" data-for="quarter year">
            <label for="year">Anno</label>
            <input class="ef-input" id="year" type="number" name="year" min="2000" max="2100" value="{{ $range->input['year'] ?? $range->from->year }}">
        </div>
        <div class="ef-field" style="margin:0" data-for="custom">
            <label for="from">Dal</label>
            <input class="ef-input" id="from" type="date" name="from" value="{{ $range->from->toDateString() }}">
        </div>
        <div class="ef-field" style="margin:0" data-for="custom">
            <label for="to">Al</label>
            <input class="ef-input" id="to" type="date" name="to" value="{{ $range->to->toDateString() }}">
        </div>
        <div><button class="ef-btn" type="submit">Applica</button></div>
    </div>
    <p class="ef-muted" style="margin:10px 0 0">Si conta la data di stipula del contratto. Periodo mostrato: <strong>{{ $range->label() }}</strong>.</p>
</form>
<script>
(function () {
    var f = document.querySelector('[data-date-filter]'); if (!f) return;
    var sel = f.querySelector('#period');
    function sync() { f.querySelectorAll('[data-for]').forEach(function (el) { el.style.display = el.getAttribute('data-for').split(' ').indexOf(sel.value) >= 0 ? '' : 'none'; }); }
    sel.addEventListener('change', sync); sync();
})();
</script>
