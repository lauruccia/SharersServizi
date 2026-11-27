{{-- Animazione 2: dal brand al cliente di quartiere, con impulsi che corrono lungo la catena. --}}
<section class="ef-section ef-soft" aria-labelledby="chain-title">
    <div class="ef-wrap">
        <div class="ef-eyebrow">Come funziona la rete</div>
        <h2 id="chain-title">Dal brand al cliente di quartiere</h2>
        <ol class="ef-chain" data-reveal>
            @foreach ([
                ['Optima, Sharers, Rapicom', 'I servizi e le piattaforme da proporre ai clienti.'],
                ['Effepi Master', 'Master Dealer: sceglie le offerte, forma e supporta la rete.'],
                ['Gli agenti', 'Conoscono i negozi della zona e li seguono da vicino.'],
                ['Il tuo negozio', 'Presidia il territorio e porta i servizi ai clienti.'],
            ] as $i => [$t, $d])
                <li class="ef-chain__item" style="--i:{{ $i }}">
                    <span class="ef-chain__n">{{ $i + 1 }}</span>
                    <h3>{{ $t }}</h3>
                    <p>{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
