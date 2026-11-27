<h2>Altri operatori mobile</h2>
<div class="ef-cols3">
    @foreach (config('effepi.pricing.sim_altri.operators') as $operator)
        <div class="ef-card"><h3>{{ $operator }}</h3><p class="ef-muted">SIM in pacchetti da 30 a 200 pezzi.</p></div>
    @endforeach
</div>
<div class="ef-block">
    @if ($canOrder)
        <a class="ef-btn" href="{{ route('orders.create', 'sim') }}">Ordina adesso</a>
    @else
        @include('partials.cannot-order')
    @endif
</div>
