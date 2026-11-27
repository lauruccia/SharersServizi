@php
    $resources = \App\Models\Resource::query()->orderBy('position')->orderBy('id')->get()->groupBy('kind');
    $topFaqs = \App\Models\Faq::query()->where('active', true)->orderBy('position')->orderBy('id')->limit(4)->get();
@endphp
<div class="ef-block">
    <h2>Moduli, grafica, video</h2>
    <div class="ef-cols3">
        @foreach (['moduli' => 'Moduli', 'grafica' => 'Grafica e locandine', 'video' => 'Video'] as $kind => $label)
            <div class="ef-card ef-card--flat"><h3>{{ $label }}</h3>@include('partials.resource-list', ['items' => $resources->get($kind, collect())])</div>
        @endforeach
    </div>
</div>
<div class="ef-block">
    <h2>Domande e risposte</h2>
    <form class="ef-card ef-search" method="get" action="{{ route('assistant') }}">
        <label for="q">Chiedi all'assistente</label>
        <div class="ef-actions">
            <input class="ef-input" id="q" type="search" name="q" maxlength="200" placeholder="Per esempio: come si paga un ordine?" required style="flex:1;min-width:220px">
            <button class="ef-btn" type="submit">Cerca la risposta</button>
        </div>
        @if ($topFaqs->isNotEmpty())
            <ul class="ef-list" style="margin-top:12px">
                @foreach ($topFaqs as $faq)<li><a class="ef-link" href="{{ route('assistant', ['q' => $faq->question]) }}">{{ $faq->question }}</a></li>@endforeach
            </ul>
        @endif
    </form>
    <p style="margin-top:12px"><a class="ef-link" href="{{ route('tickets.create') }}">Non trovi la risposta? Apri una segnalazione</a> · <a class="ef-link" href="{{ route('tickets.index') }}">Le tue segnalazioni</a></p>
</div>
