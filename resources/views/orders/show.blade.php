@extends('layouts.area')
@section('title', 'Ordine '.$order->number)
@if (auth()->user()->isBackOffice())@section('nav')@include('partials.manager-nav')@endsection @endif

@section('content')
<div class="ef-form-page">
<p><a class="ef-link" href="{{ auth()->user()->isBackOffice() ? route('manager.orders') : route('orders.index') }}">← Tutti gli ordini</a></p>
<h1>{{ $order->typeLabel() }} {{ $order->number }}</h1>
<p>
    @if ($order->isPaid())<span class="ef-badge ef-badge--ok">Pagato il {{ $order->paid_at->format('d/m/Y') }}</span>
    @else<span class="ef-badge ef-badge--warn">In attesa di pagamento</span>@endif
    · {{ $order->paymentLabel() }} · ordine del {{ $order->order_date->format('d/m/Y') }}
</p>

@unless ($order->isPaid())
    <div class="ef-alert ef-alert--warn">
        La merce verrà evasa solo alla ricezione del pagamento.
        @if ($order->payment_method === 'bonifico')
            @if (config('effepi.bank.iban'))
                Bonifico a <strong>{{ config('effepi.bank.holder') }}</strong>, IBAN <strong>{{ config('effepi.bank.iban') }}</strong>, causale <strong>{{ $order->number }}</strong>.
            @else
                Riceverai le coordinate per il bonifico dal back office. Causale: <strong>{{ $order->number }}</strong>.
            @endif
        @endif
    </div>
@endunless

<div class="ef-cols2 ef-block">
    <div class="ef-card ef-card--flat"><div class="ef-label">Ragione sociale</div>{{ $order->business_name }}<div class="ef-label" style="margin-top:12px">Indirizzo di consegna</div>{{ $order->delivery_address }}</div>
    <div class="ef-card ef-card--flat"><div class="ef-label">Email cliente</div>{{ $order->customer_email }}<div class="ef-label" style="margin-top:12px">Agente</div>{{ $order->agent_name ?? '—' }}@if ($order->goods_type)<div class="ef-label" style="margin-top:12px">Tipo di merce</div>{{ $order->goods_type }}@endif</div>
</div>

<div class="ef-block">@include('partials.order-summary')</div>
@if ($order->notes)<div class="ef-block"><div class="ef-label">Note</div><p>{{ $order->notes }}</p></div>@endif
</div>
@endsection
