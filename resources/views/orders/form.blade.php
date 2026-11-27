@extends('layouts.area')
@section('title', $title)

@php
    $first = $dealers->first();
    $selected = $dealers->firstWhere('id', (int) old('dealer_id', $first->id)) ?? $first;
    $pricing = config('effepi.pricing');
    $euro = fn ($v) => number_format($v, 2, ',', '.').' €';
@endphp

@section('content')
<div class="ef-form-page">
<p><a class="ef-link" href="{{ route(auth()->user()->homeRoute()) }}">← Torna all'area riservata</a></p>
<h1>{{ $title }}</h1>
<p>L'ordine viene inviato a {{ config('effepi.mail.orders') }}.</p>

<form class="ef-card" method="post" action="{{ route('orders.store', $slug) }}" data-order="{{ $type }}"
      data-pricing='@json($pricing)'>
    @csrf

    @if ($isAgent)
        <div class="ef-field">
            <label for="dealer_id">Dealer per cui ordini</label>
            <select class="ef-input" id="dealer_id" name="dealer_id" required data-dealer-select>
                @foreach ($dealers as $d)
                    <option value="{{ $d->id }}" @selected($selected->id === $d->id)
                            data-name="{{ $d->business_name }}" data-address="{{ $d->operating_address }}" data-email="{{ $d->email }}">{{ $d->business_name }}</option>
                @endforeach
            </select>
            <div class="ef-muted">Solo i dealer del tuo portafoglio con anagrafica completa.</div>
        </div>
    @else
        <input type="hidden" name="dealer_id" value="{{ $selected->id }}">
    @endif

    <div class="ef-field">
        <label for="business_name">Ragione sociale</label>
        <input class="ef-input" id="business_name" type="text" name="business_name" value="{{ old('business_name', $selected->business_name) }}" required>
    </div>
    <div class="ef-field">
        <label for="delivery_address">Indirizzo di consegna</label>
        <input class="ef-input" id="delivery_address" type="text" name="delivery_address" value="{{ old('delivery_address', $selected->operating_address) }}" autocomplete="street-address" required>
    </div>
    <div class="ef-field">
        <label for="customer_email">Email cliente</label>
        <input class="ef-input" id="customer_email" type="email" name="customer_email" value="{{ old('customer_email', $selected->email) }}" required>
        <div class="ef-muted">Il cliente riceverà una copia dell'ordine a questo indirizzo.</div>
    </div>
    <div class="ef-cols2">
        <div class="ef-field">
            <label for="agent_name">Agente</label>
            <input class="ef-input ef-input--readonly" id="agent_name" type="text" value="{{ $isAgent ? auth()->user()->name : ($selected->agent?->user?->name ?? '—') }}" readonly>
        </div>
        <div class="ef-field">
            <label for="order_date">Data ordine</label>
            <input class="ef-input" id="order_date" type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required>
        </div>
    </div>

    @if ($type === 'optima')
        <fieldset class="ef-fieldset">
            <legend>Plafond ricariche</legend>
            <div class="ef-order-row">
                <div class="ef-field">
                    <label for="plafond">Importo plafond</label>
                    <select class="ef-input" id="plafond" name="plafond" data-calc>
                        <option value="">Nessuno</option>
                        @foreach ($pricing['plafond']['amounts'] as $amount)<option value="{{ $amount }}" @selected((int) old('plafond') === $amount)>{{ number_format($amount, 0, ',', '.') }} €</option>@endforeach
                    </select>
                </div>
                <div><div class="ef-label">Sconto</div><div class="ef-order-val" data-out="plafond-discount">–</div></div>
                <div><div class="ef-label">Costo</div><div class="ef-order-val" data-out="plafond-cost">0,00 €</div></div>
            </div>
            <div class="ef-muted">Sconto {{ $pricing['plafond']['discount_low'] }}% fino a {{ $pricing['plafond']['threshold'] }} €, {{ $pricing['plafond']['discount_high'] }}% oltre. {{ config('effepi.vat_note_art74') }}</div>
        </fieldset>
        <fieldset class="ef-fieldset">
            <legend>SIM Optima</legend>
            <div class="ef-order-row">
                <div class="ef-field">
                    <label for="sim_quantity">Quantità (multipli di {{ $pricing['sim_optima']['multiple'] }})</label>
                    <input class="ef-input" id="sim_quantity" type="number" name="sim_quantity" min="0" max="{{ $pricing['sim_optima']['max_quantity'] }}" step="{{ $pricing['sim_optima']['multiple'] }}" value="{{ old('sim_quantity') }}" data-calc>
                </div>
                <div><div class="ef-label">Prezzo per SIM</div><div class="ef-order-val" data-out="sim-unit">–</div></div>
                <div><div class="ef-label">Costo</div><div class="ef-order-val" data-out="sim-cost">0,00 €</div></div>
            </div>
            <div class="ef-muted">{{ $euro($pricing['sim_optima']['price_low_qty']) }} a SIM fino a {{ $pricing['sim_optima']['threshold'] }} SIM, {{ $euro($pricing['sim_optima']['price_high_qty']) }} a SIM su tutte oltre le {{ $pricing['sim_optima']['threshold'] }}.</div>
        </fieldset>
        <div class="ef-order-line"><span>Spese di trasporto</span><strong data-out="shipping">0,00 €</strong></div>
        <p class="ef-muted" style="text-align:right">{{ $euro($pricing['sim_optima']['shipping']) }} con le SIM, non soggetto a IVA ai sensi dell'art. 74 DPR 633/72. Nessuna spedizione per il solo plafond.</p>

    @elseif ($type === 'altri_operatori')
        <p class="ef-muted">Pacchetti disponibili (prezzi per SIM):
            @foreach (collect($pricing['sim_altri']['packages'])->groupBy(fn ($price) => (string) $price, true) as $price => $group)
                {{ $group->keys()->join(', ', ' o ') }} pz a {{ $euro((float) $price) }}@if (! $loop->last) · @endif
            @endforeach.
            {{ config('effepi.vat_note_art74') }}</p>
        @foreach ($pricing['sim_altri']['operators'] as $i => $operator)
            <fieldset class="ef-fieldset">
                <legend>{{ $operator }}</legend>
                <div class="ef-order-row">
                    <div class="ef-field">
                        <label for="package-{{ $i }}">Pacchetto</label>
                        <select class="ef-input" id="package-{{ $i }}" name="packages[{{ $i }}]" data-calc data-package="{{ $i }}">
                            <option value="0">Nessuno</option>
                            @foreach (array_keys($pricing['sim_altri']['packages']) as $pieces)<option value="{{ $pieces }}" @selected((int) old("packages.$i") === $pieces)>{{ $pieces }} pezzi</option>@endforeach
                        </select>
                    </div>
                    <div><div class="ef-label">Prezzo per SIM</div><div class="ef-order-val" data-out="package-unit-{{ $i }}">–</div></div>
                    <div><div class="ef-label">Costo</div><div class="ef-order-val" data-out="package-cost-{{ $i }}">0,00 €</div></div>
                </div>
            </fieldset>
        @endforeach
        <div class="ef-order-line"><span>Spese di trasporto</span><strong data-out="shipping">0,00 €</strong></div>
        <p class="ef-muted" style="text-align:right">{{ $euro($pricing['sim_altri']['shipping']) }} per ordine, non soggetto a IVA ai sensi dell'art. 74 DPR 633/72.</p>

    @elseif ($type === 'promo')
        <p class="ef-muted">Prodotto e prezzo arrivano dalla promo dedicata: inserisci solo i pezzi.</p>
        @if ($promos->isEmpty())
            <div class="ef-alert ef-alert--warn">In questo momento non ci sono promo attive.</div>
        @else
            <div class="ef-table-wrap" style="margin-bottom:16px"><table class="ef-table">
                <thead><tr><th>Pezzi da ordinare</th><th>Prodotto in offerta</th><th>Prezzo unitario (€, IVA compresa)</th><th>Scade</th></tr></thead>
                <tbody>
                @foreach ($promos as $promo)
                    <tr>
                        <td style="width:140px"><input class="ef-input" type="number" name="promos[{{ $promo->id }}]" min="0" max="999" step="1" value="{{ old('promos.'.$promo->id, 0) }}" aria-label="Pezzi di {{ $promo->name }}" data-calc data-promo-price="{{ $promo->price }}"></td>
                        <td><strong>{{ $promo->name }}</strong><br><span class="ef-muted">{{ $promo->label() }}</span></td>
                        <td>{{ $euro((float) $promo->price) }}</td>
                        <td>{{ $promo->ends_at->isoFormat('ddd D MMM, HH:mm') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
        <p class="ef-muted" style="text-align:right">Spese di trasporto escluse: te le comunica il back office alla conferma dell'ordine.</p>

    @else
        <div class="ef-table-wrap" style="margin-bottom:16px"><table class="ef-table">
            <thead><tr><th>Quantità</th><th>Descrizione</th><th>Importo unitario (€, IVA compresa)</th></tr></thead>
            <tbody>
            @for ($i = 0; $i < 5; $i++)
                <tr>
                    <td style="width:120px"><input class="ef-input" type="number" name="rows[{{ $i }}][quantity]" min="0" max="9999" step="1" value="{{ old("rows.$i.quantity") }}" aria-label="Quantità riga {{ $i + 1 }}" data-calc data-row-qty="{{ $i }}"></td>
                    <td><input class="ef-input" type="text" name="rows[{{ $i }}][description]" maxlength="255" value="{{ old("rows.$i.description") }}" aria-label="Descrizione riga {{ $i + 1 }}"></td>
                    <td style="width:200px"><input class="ef-input" type="number" name="rows[{{ $i }}][unit_price]" min="0" step="0.01" value="{{ old("rows.$i.unit_price") }}" aria-label="Importo unitario riga {{ $i + 1 }}" data-calc data-row-price="{{ $i }}"></td>
                </tr>
            @endfor
            </tbody>
        </table></div>
        <div class="ef-field">
            <label for="goods_type">Tipo di merce</label>
            <input class="ef-input" id="goods_type" type="text" name="goods_type" maxlength="120" value="{{ old('goods_type') }}" required>
        </div>
        <p class="ef-muted" style="text-align:right">Spese di trasporto escluse: te le comunica il back office alla conferma dell'ordine.</p>
    @endif

    <div class="ef-order-total"><span>{{ in_array($type, ['promo', 'generico']) ? 'Totale IVA compresa' : 'Totale ordine' }}</span><strong data-out="total">0,00 €</strong></div>

    <div class="ef-field">
        <label for="payment_method">Modalità di pagamento</label>
        <select class="ef-input" id="payment_method" name="payment_method" required>
            @foreach (config('effepi.payment_methods') as $method)<option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ \App\Models\Order::PAYMENT_METHODS[$method] }}</option>@endforeach
        </select>
        <div class="ef-muted">La merce verrà evasa solo alla ricezione del pagamento.</div>
    </div>
    <div class="ef-field">
        <label for="notes">Note</label>
        <textarea class="ef-input" id="notes" name="notes" maxlength="2000">{{ old('notes') }}</textarea>
    </div>
    <button class="ef-btn" type="submit" @disabled($type === 'promo' && $promos->isEmpty())>Invia l'ordine</button>
</form>
</div>
@endsection
