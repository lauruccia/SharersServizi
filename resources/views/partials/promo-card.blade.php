{{-- Card di una promo con conto alla rovescia. $promo, $orderUrl (facoltativo) --}}
<article class="ef-promo" data-ends-at="{{ $promo->ends_at->toIso8601String() }}">
    <div class="ef-promo__timer">
        <small>{{ $promo->label() }} · scade tra</small>
        <div class="ef-promo__clock" data-clock aria-live="off">--:--:--</div>
    </div>
    <div class="ef-promo__photo">
        @if ($promo->photoUrl())<img src="{{ $promo->photoUrl() }}" alt="{{ $promo->name }}" loading="lazy">@else Foto prodotto @endif
    </div>
    <p class="ef-promo__name">{{ $promo->name }}</p>
    <p class="ef-promo__price">{{ number_format($promo->price, 2, ',', '.') }} €</p>
    @isset($orderUrl)<a class="ef-btn ef-btn--small" href="{{ $orderUrl }}">Ordina adesso</a>@endisset
    {{ $slot ?? '' }}
</article>
