{{-- Scheda Promo Dedicate, uguale per dealer e agente. $canOrder decide se mostrare "Ordina adesso". --}}
<div data-server-now="{{ now()->toIso8601String() }}">
    <h2>Promo Dedicate</h2>
    <p>Le promo dedicate sui prodotti del sito.</p>
    <div class="ef-cols3">
        @foreach ($promos as $promo)
            @include('partials.promo-card', ['promo' => $promo, 'orderUrl' => $canOrder ? route('orders.create', 'promo') : null])
        @endforeach
    </div>
    <div class="ef-empty" data-promo-empty @if ($promos->isNotEmpty()) hidden @endif>In questo momento non ci sono promo attive. Torna a trovarci: le offerte durano poche ore.</div>
</div>

<div class="ef-block">
    <h2>Dal catalogo Sharers</h2>
    <p>Apri la scheda del prodotto direttamente sul sito.</p>
    @if ($showcase->isEmpty())
        @include('partials.pending-data', ['text' => 'I prodotti in vetrina verranno pubblicati a breve.'])
    @else
        <div class="ef-cols3">
            @foreach ($showcase as $product)
                <article class="ef-promo">
                    <div class="ef-promo__photo">@if ($product->photoUrl())<img src="{{ $product->photoUrl() }}" alt="{{ $product->name }}" loading="lazy">@else Foto prodotto @endif</div>
                    <p class="ef-promo__name">{{ $product->name }}</p>
                    <a class="ef-btn ef-btn--small ef-btn--ghost" href="{{ $product->url }}" target="_blank" rel="noopener">Vedi su sharers.it</a>
                </article>
            @endforeach
        </div>
    @endif
</div>
