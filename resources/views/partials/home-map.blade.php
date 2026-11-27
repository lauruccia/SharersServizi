{{-- Mappa animata dell'hero: Effepi (Lazio) invia segnali alle regioni, le regioni si accendono a turno, i servizi fluttuano attorno. --}}
@php
    // Coordinate in pixel sull'immagine mappa-centro-sud.png (520 x 579).
    $spots = [
        'Umbria' => [180, 140], 'Marche' => [292, 168], 'Abruzzo' => [362, 252], 'Molise' => [402, 288],
        'Puglia' => [458, 316], 'Basilicata' => [397, 352], 'Calabria' => [396, 418], 'Sicilia' => [296, 462],
        'Sardegna' => [76, 312],
    ];
    $hub = [228, 216]; // Lazio: la sede Effepi
    $pins = collect($spots)->filter(fn ($_, $name) => in_array($name, $regions, true));
    $pct = fn (int $v, int $max) => round($v / $max * 100, 2).'%';
    $ico = [
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'wifi' => '<path d="M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/>',
        'sim' => '<path d="M6 2h8l4 4v16H6z"/><path d="M9 12h6v6H9z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20"/>',
    ];
    $chips = [['bolt', 'Luce e gas', 'blue'], ['wifi', 'Fibra', 'magenta'], ['sim', 'SIM', 'lime'], ['globe', 'Web', 'blue']];
@endphp
<div class="ef-hero__map">
    <span class="ef-chip ef-chip--1 ef-chip--{{ $chips[0][2] }}"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ico[$chips[0][0]] !!}</svg></i>{{ $chips[0][1] }}</span>
    <span class="ef-chip ef-chip--2 ef-chip--{{ $chips[1][2] }}"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ico[$chips[1][0]] !!}</svg></i>{{ $chips[1][1] }}</span>
    <span class="ef-chip ef-chip--3 ef-chip--{{ $chips[2][2] }}"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ico[$chips[2][0]] !!}</svg></i>{{ $chips[2][1] }}</span>
    <span class="ef-chip ef-chip--4 ef-chip--{{ $chips[3][2] }}"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ico[$chips[3][0]] !!}</svg></i>{{ $chips[3][1] }}</span>

    <div class="ef-map" data-map>
        <img src="{{ asset('img/mappa-centro-sud.png') }}" alt="Mappa dell'Italia con le regioni del Centro Sud" width="520" height="579">
        <svg class="ef-map__lines" viewBox="0 0 520 579" aria-hidden="true" focusable="false">
            @foreach ($pins as $name => [$x, $y])
                <line x1="{{ $hub[0] }}" y1="{{ $hub[1] }}" x2="{{ $x }}" y2="{{ $y }}"/>
            @endforeach
        </svg>
        @foreach ($pins as $name => [$x, $y])
            <span class="ef-pin" data-pin style="--x:{{ $pct($x, 520) }};--y:{{ $pct($y, 579) }};--d:{{ 0.9 + $loop->index * 0.18 }}s" aria-hidden="true"><b>{{ $name }}</b></span>
        @endforeach
        @if (in_array('Lazio', $regions, true))
            <span class="ef-pin ef-pin--hub" style="--x:{{ $pct($hub[0], 520) }};--y:{{ $pct($hub[1], 579) }};--d:.5s" aria-hidden="true"><b>Effepi · Lazio</b></span>
        @endif
    </div>
    <p class="ef-muted">{{ implode(' · ', $regions) }}</p>
</div>
