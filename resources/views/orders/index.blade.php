@extends('layouts.area')
@section('title', 'Ordini')

@section('content')
<p><a class="ef-link" href="{{ route(auth()->user()->homeRoute()) }}">← Torna all'area riservata</a></p>
<h1>Ordini</h1>
<p>{{ ['agent' => 'Gli ordini dei dealer del tuo portafoglio.', 'capoarea' => 'Gli ordini dei dealer dei tuoi agenti.'][auth()->user()->role] ?? 'Gli ordini del tuo negozio.' }}</p>
@if ($orders->isEmpty())
    <div class="ef-empty">Ancora nessun ordine.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Data</th><th>Ordine</th>@if (auth()->user()->role !== 'dealer')<th>Dealer</th>@endif<th>Tipo</th><th>Totale</th><th>Pagamento</th></tr></thead>
    <tbody>
    @foreach ($orders as $order)
        <tr>
            <td>{{ $order->order_date->format('d/m/Y') }}</td>
            <td><a class="ef-link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
            @if (auth()->user()->role !== 'dealer')<td>{{ $order->business_name }}</td>@endif
            <td>{{ $order->typeLabel() }}</td>
            <td>{{ number_format($order->total, 2, ',', '.') }} €</td>
            <td>@if ($order->isPaid())<span class="ef-badge ef-badge--ok">pagato</span>@else<span class="ef-badge ef-badge--warn">in attesa</span>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
