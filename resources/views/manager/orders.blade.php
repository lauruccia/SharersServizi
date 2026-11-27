@extends('layouts.area')
@section('title', 'Area manager: ordini')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Ordini</h1>
<p>Tutti gli ordini inviati da dealer e agenti. Quando arriva il bonifico, segna l'ordine come pagato.</p>
@if ($orders->isEmpty())
    <div class="ef-empty">Ancora nessun ordine.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Data</th><th>Ordine</th><th>Dealer</th><th>Inserito da</th><th>Tipo</th><th>Totale</th><th>Pagamento</th></tr></thead>
    <tbody>
    @foreach ($orders as $order)
        <tr>
            <td>{{ $order->order_date->format('d/m/Y') }}</td>
            <td><a class="ef-link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
            <td>{{ $order->business_name }}</td>
            <td>{{ $order->user?->name ?? '—' }}</td>
            <td>{{ $order->typeLabel() }}</td>
            <td>{{ number_format($order->total, 2, ',', '.') }} €@if ($order->shipping_cost === null)<br><span class="ef-muted">+ trasporto</span>@endif</td>
            <td>
                @if ($order->isPaid())
                    <span class="ef-badge ef-badge--ok">pagato il {{ $order->paid_at->format('d/m') }}</span>
                @else
                    <form method="post" action="{{ route('manager.orders.paid', $order) }}">@csrf<button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Segna pagato</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
