{{-- Animazione 3: il percorso di adesione, con la linea che si riempie e i passaggi che si accendono. --}}
<section class="ef-section" aria-labelledby="steps-title">
    <div class="ef-wrap">
        <div class="ef-eyebrow">Come si entra</div>
        <h2 id="steps-title">Dal primo contatto al primo ordine</h2>
        <ol class="ef-steps" data-reveal>
            @foreach ([
                ['Ti registri', 'Compili il modulo da dealer o da agente e scegli l\'orario per la videocall.'],
                ['Videocall con noi', 'Ci conosciamo e verifichiamo la disponibilità nella tua zona.'],
                ['Approvazione e accesso', 'Se ci siamo, ricevi via email le credenziali per l\'area riservata.'],
                ['Inizi a lavorare', 'Ordini, Promo Dedicate, materiali e assistenza: tutto dal portale.'],
            ] as $i => [$t, $d])
                <li class="ef-steps__item" style="--i:{{ $i }}">
                    <span class="ef-steps__dot">{{ $i + 1 }}</span>
                    <h3>{{ $t }}</h3>
                    <p>{{ $d }}</p>
                </li>
            @endforeach
        </ol>
        <p style="margin-top:28px"><a class="ef-btn" href="#registrati">Parti da qui</a></p>
    </div>
</section>
