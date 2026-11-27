{{-- Riepilogo di un ordine: usato nella pagina dell'ordine. --}}
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Descrizione</th><th>Quantità</th><th>Prezzo unitario</th><th>Importo</th></tr></thead>
    <tbody>
    @foreach ($order->items as $item)
        <tr>
            <td>{{ $item->description }}@if ($item->note)<br><span class="ef-muted">{{ $item->note }}</span>@endif</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
            <td>{{ number_format($item->amount, 2, ',', '.') }} €</td>
        </tr>
    @endforeach
    <tr><td colspan="3">Spese di trasporto</td><td>{{ $order->shipping_cost === null ? 'da quantificare' : number_format($order->shipping_cost, 2, ',', '.').' €' }}</td></tr>
    <tr><td colspan="3"><strong>Totale{{ $order->shipping_cost === null ? ' (trasporto escluso)' : '' }}</strong></td><td><strong>{{ number_format($order->total, 2, ',', '.') }} €</strong></td></tr>
    </tbody>
</table></div>
@if ($order->vat_note)<p class="ef-muted" style="margin-top:8px">{{ $order->vat_note }}</p>@endif
