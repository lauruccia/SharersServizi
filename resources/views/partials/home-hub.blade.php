{{-- Animazione 1: il negozio al centro, i servizi che arrivano, Effepi che guida. Solo HTML/CSS/SVG. --}}
@php
    $svg = [
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'wifi' => '<path d="M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/>',
        'sim' => '<path d="M6 2h8l4 4v16H6z"/><path d="M9 12h6v6H9z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'phone' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
        'shop' => '<path d="M3 9l1.5-5h15L21 9zM4 9v11h16V9M10 20v-6h4v6"/>',
    ];
    $icon = fn (string $k) => '<svg class="ef-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$svg[$k].'</svg>';
    // x, y su una griglia 100 x 56,25; colore dell'icona
    $nodes = [
        ['bolt',  'Luce e gas',            'Optima',  13, 10,   'blue'],
        ['wifi',  'Fibra',                 'Optima',   9, 28,   'blue'],
        ['sim',   'SIM',                   'Optima',  13, 46,   'blue'],
        ['globe', 'Negozio online', 'Sharers', 87, 10,  'magenta'],
        ['card',  'Pagamenti e ricariche', 'Rapicom', 91, 28,   'lime'],
        ['phone', 'Altri operatori mobile', 'Altre proposte', 87, 46, 'lime'],
    ];
@endphp
<section class="ef-section" id="senso" aria-labelledby="hub-title">
    <div class="ef-wrap">
        <div class="ef-eyebrow">Il senso del progetto</div>
        <h2 id="hub-title">Il tuo negozio diventa un hub di servizi</h2>
        <p class="ef-hero__lead">Tu presidi il territorio. Effepi ti guida nelle scelte giuste e ti porta in negozio i servizi di luce, gas, fibra, mobile, web e pagamenti digitali: più motivi per entrare, più clienti che tornano.</p>

        <div class="ef-hub" data-reveal>
            <svg class="ef-hub__lines" viewBox="0 0 100 56.25" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <line x1="50" y1="6" x2="50" y2="28"/>
                @foreach ($nodes as [$k, $t, $b, $x, $y, $c])
                    <line x1="{{ $x }}" y1="{{ $y }}" x2="50" y2="28"/>
                @endforeach
            </svg>

            <div class="ef-hub__node ef-hub__node--effepi" style="--x:50;--y:6;--i:0">
                <img src="{{ asset('img/simbolo-effepi.svg') }}" alt="" width="34" height="32">
                <span><strong>Effepi</strong><small>ti guida nelle scelte giuste</small></span>
            </div>

            @foreach ($nodes as $n => [$k, $t, $b, $x, $y, $c])
                <div class="ef-hub__node ef-hub__node--{{ $c }}" style="--x:{{ $x }};--y:{{ $y }};--i:{{ $n + 1 }}">
                    {!! $icon($k) !!}
                    <span><strong>{{ $t }}</strong><small>{{ $b }}</small></span>
                </div>
            @endforeach

            <div class="ef-hub__center" style="--x:50;--y:28" aria-hidden="false">
                <i class="ef-hub__ring"></i><i class="ef-hub__ring ef-hub__ring--2"></i>
                <div class="ef-hub__core">{!! $icon('shop') !!}<strong>Il tuo negozio</strong><small>al centro del quartiere</small></div>
            </div>
        </div>
        <p class="ef-cta-row"><a class="ef-btn" href="#registrati">Porta questi servizi nel tuo negozio</a></p>
    </div>
</section>
