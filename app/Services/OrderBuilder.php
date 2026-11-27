<?php

namespace App\Services;

use App\Models\Promo;
use InvalidArgumentException;

/**
 * Trasforma i dati dei quattro moduli d'ordine in righe, trasporto e totale.
 * I prezzi non arrivano mai dal browser (tranne nell'ordine generico, dove li scrive chi ordina).
 */
class OrderBuilder
{
    public function __construct(private OrderPricing $pricing) {}

    /**
     * @return array{items: list<array>, shipping: ?float, total: float, vat_note: string, goods_type: ?string}
     */
    public function build(string $type, array $input): array
    {
        return match ($type) {
            'optima' => $this->optima($input),
            'altri_operatori' => $this->otherOperators($input),
            'promo' => $this->promo($input),
            'generico' => $this->generic($input),
        };
    }

    private function optima(array $input): array
    {
        $plafond = ! empty($input['plafond']) ? (float) $input['plafond'] : null;
        $sims = ! empty($input['sim_quantity']) ? (int) $input['sim_quantity'] : null;
        $calc = $this->pricing->optimaOrder($plafond, $sims);

        $items = [];
        if ($p = $calc['plafond']) {
            $items[] = [
                'description' => 'Plafond ricariche Optima '.$this->money($p['amount']),
                'quantity' => 1,
                'unit_price' => $p['cost'],
                'amount' => $p['cost'],
                'note' => 'Sconto '.$p['discount_percent'].'% ('.$this->money($p['discount']).')',
            ];
        }
        if ($s = $calc['sims']) {
            $items[] = ['description' => 'SIM Optima', 'quantity' => $s['quantity'], 'unit_price' => $s['unit_price'], 'amount' => $s['cost']];
        }

        return ['items' => $items, 'shipping' => $calc['shipping'], 'total' => $calc['total'], 'vat_note' => config('effepi.vat_note_art74'), 'goods_type' => null];
    }

    private function otherOperators(array $input): array
    {
        $operators = config('effepi.pricing.sim_altri.operators');
        $packages = [];
        foreach ($operators as $i => $operator) {
            $packages[$operator] = (int) ($input['packages'][$i] ?? 0);
        }
        $calc = $this->pricing->otherOperatorsOrder($packages);
        $items = array_map(fn (array $l) => [
            'description' => 'SIM '.$l['operator'].' (pacchetto da '.$l['quantity'].' pezzi)',
            'quantity' => $l['quantity'], 'unit_price' => $l['unit_price'], 'amount' => $l['cost'],
        ], $calc['lines']);

        return ['items' => $items, 'shipping' => $calc['shipping'], 'total' => $calc['total'], 'vat_note' => config('effepi.vat_note_art74'), 'goods_type' => null];
    }

    /** Prodotto e prezzo arrivano dalla promo: dal modulo si leggono solo i pezzi. */
    private function promo(array $input): array
    {
        $wanted = array_filter(array_map('intval', $input['promos'] ?? []), fn (int $q) => $q > 0);
        if (! $wanted) {
            throw new InvalidArgumentException('Indica i pezzi di almeno un\'offerta.');
        }
        // Solo offerte ancora in corso in questo momento.
        $promos = Promo::query()->active()->whereIn('id', array_keys($wanted))->get()->keyBy('id');
        if ($promos->count() !== count($wanted)) {
            throw new InvalidArgumentException('Una delle offerte scelte è scaduta: ricarica la pagina e riprova.');
        }
        $items = [];
        foreach ($wanted as $id => $quantity) {
            $promo = $promos[$id];
            $items[] = [
                'description' => $promo->name.' ('.$promo->label().')',
                'quantity' => $quantity, 'unit_price' => (float) $promo->price,
                'amount' => round($quantity * (float) $promo->price, 2), 'promo_id' => $promo->id,
            ];
        }

        return ['items' => $items, 'shipping' => null, 'total' => round(array_sum(array_column($items, 'amount')), 2), 'vat_note' => 'Prezzi IVA compresa.', 'goods_type' => 'Promo Dedicate'];
    }

    private function generic(array $input): array
    {
        $items = [];
        foreach ($input['rows'] ?? [] as $row) {
            $quantity = (int) ($row['quantity'] ?? 0);
            $description = trim((string) ($row['description'] ?? ''));
            $price = (float) str_replace(',', '.', (string) ($row['unit_price'] ?? 0));
            if ($quantity <= 0 && $description === '') {
                continue; // riga vuota
            }
            if ($quantity <= 0 || $description === '' || $price <= 0) {
                throw new InvalidArgumentException('Ogni riga compilata deve avere quantità, descrizione e importo.');
            }
            $items[] = ['description' => $description, 'quantity' => $quantity, 'unit_price' => $price, 'amount' => round($quantity * $price, 2)];
        }
        if (! $items) {
            throw new InvalidArgumentException('Compila almeno una riga dell\'ordine.');
        }

        return ['items' => $items, 'shipping' => null, 'total' => round(array_sum(array_column($items, 'amount')), 2), 'vat_note' => 'Prezzi IVA compresa.', 'goods_type' => $input['goods_type'] ?? null];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', '.').' €';
    }
}
