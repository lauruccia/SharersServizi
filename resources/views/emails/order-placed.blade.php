@extends('emails.layout')
@section('content')
<p><strong>{{ $order->typeLabel() }} {{ $order->number }}</strong> del {{ $order->order_date->format('d/m/Y') }}</p>
<p>Ragione sociale: <strong>{{ $order->business_name }}</strong><br>
Indirizzo di consegna: {{ $order->delivery_address }}<br>
Email cliente: {{ $order->customer_email }}<br>
Agente: {{ $order->agent_name ?? '—' }}@if ($order->goods_type)<br>Tipo di merce: {{ $order->goods_type }}@endif</p>
<table style="width:100%;border-collapse:collapse;font-size:14px">
    <tr style="background:#f2f3f7"><th align="left" style="padding:8px">Descrizione</th><th align="right" style="padding:8px">Q.tà</th><th align="right" style="padding:8px">Prezzo</th><th align="right" style="padding:8px">Importo</th></tr>
    @foreach ($order->items as $item)
        <tr><td style="padding:8px;border-bottom:1px solid #dfe2e8">{{ $item->description }}@if ($item->note)<br><span style="color:#6b7280">{{ $item->note }}</span>@endif</td>
            <td align="right" style="padding:8px;border-bottom:1px solid #dfe2e8">{{ $item->quantity }}</td>
            <td align="right" style="padding:8px;border-bottom:1px solid #dfe2e8">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
            <td align="right" style="padding:8px;border-bottom:1px solid #dfe2e8">{{ number_format($item->amount, 2, ',', '.') }} €</td></tr>
    @endforeach
    <tr><td colspan="3" style="padding:8px">Spese di trasporto</td><td align="right" style="padding:8px">{{ $order->shipping_cost === null ? 'da quantificare' : number_format($order->shipping_cost, 2, ',', '.').' €' }}</td></tr>
    <tr><td colspan="3" style="padding:8px"><strong>Totale{{ $order->shipping_cost === null ? ' (trasporto escluso)' : '' }}</strong></td><td align="right" style="padding:8px"><strong>{{ number_format($order->total, 2, ',', '.') }} €</strong></td></tr>
</table>
@if ($order->vat_note)<p style="color:#6b7280;font-size:13px">{{ $order->vat_note }}</p>@endif
<p>Modalità di pagamento: <strong>{{ $order->paymentLabel() }}</strong>. La merce verrà evasa solo alla ricezione del pagamento.
@if ($order->payment_method === 'bonifico' && config('effepi.bank.iban'))<br>Bonifico a {{ config('effepi.bank.holder') }}, IBAN {{ config('effepi.bank.iban') }}, causale {{ $order->number }}.@endif</p>
@if ($order->notes)<p>Note: {{ $order->notes }}</p>@endif
@endsection
