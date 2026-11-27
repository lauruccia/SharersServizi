<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Regole di prezzo degli ordini (docs/04-ordini-e-prezzi.md).
 * Unico punto in cui si calcolano i totali: il browser mostra solo un'anteprima.
 */
class OrderPricing
{
    /** Plafond ricariche Optima: sconto 4% fino a 300 €, 6% oltre. */
    public function plafond(float $amount): array
    {
        $cfg = config('effepi.pricing.plafond');
        if ($amount < $cfg['min'] || $amount > $cfg['max'] || ! in_array((int) $amount, $cfg['amounts'], true) || (float) (int) $amount !== $amount) {
            throw new InvalidArgumentException("Il plafond deve essere uno dei tagli previsti, tra {$cfg['min']} e {$cfg['max']} euro.");
        }
        $percent = $amount <= $cfg['threshold'] ? $cfg['discount_low'] : $cfg['discount_high'];
        $discount = round($amount * $percent / 100, 2);

        return ['amount' => $amount, 'discount_percent' => $percent, 'discount' => $discount, 'cost' => round($amount - $discount, 2)];
    }

    /** SIM Optima: multipli di 20; 3,50 € fino a 40, oltre le 40 2,50 € su tutte. */
    public function simOptima(int $quantity): array
    {
        $cfg = config('effepi.pricing.sim_optima');
        if ($quantity <= 0 || $quantity > $cfg['max_quantity'] || $quantity % $cfg['multiple'] !== 0) {
            throw new InvalidArgumentException("Le SIM Optima si ordinano a multipli di {$cfg['multiple']}.");
        }
        $unit = $quantity <= $cfg['threshold'] ? $cfg['price_low_qty'] : $cfg['price_high_qty'];

        return ['quantity' => $quantity, 'unit_price' => $unit, 'cost' => round($quantity * $unit, 2)];
    }

    /**
     * Ordine Optima completo: plafond e/o SIM. Trasporto solo se ci sono SIM.
     */
    public function optimaOrder(?float $plafond, ?int $sims): array
    {
        $p = $plafond ? $this->plafond($plafond) : null;
        $s = $sims ? $this->simOptima($sims) : null;
        if (! $p && ! $s) {
            throw new InvalidArgumentException('Scegli un plafond o una quantità di SIM.');
        }
        $shipping = $s ? (float) config('effepi.pricing.sim_optima.shipping') : 0.0;

        return [
            'plafond' => $p,
            'sims' => $s,
            'shipping' => $shipping,
            'total' => round(($p['cost'] ?? 0) + ($s['cost'] ?? 0) + $shipping, 2),
        ];
    }

    /**
     * SIM altri operatori: un pacchetto per operatore, trasporto fisso per ordine.
     *
     * @param  array<string, int>  $packages  operatore => pezzi
     */
    public function otherOperatorsOrder(array $packages): array
    {
        $cfg = config('effepi.pricing.sim_altri');
        $lines = [];
        foreach ($packages as $operator => $pieces) {
            if (! $pieces) {
                continue;
            }
            if (! in_array($operator, $cfg['operators'], true) || ! isset($cfg['packages'][$pieces])) {
                throw new InvalidArgumentException('Pacchetto non valido.');
            }
            $unit = $cfg['packages'][$pieces];
            $lines[] = ['operator' => $operator, 'quantity' => $pieces, 'unit_price' => $unit, 'cost' => round($pieces * $unit, 2)];
        }
        if (! $lines) {
            throw new InvalidArgumentException('Scegli almeno un pacchetto.');
        }
        $shipping = (float) $cfg['shipping'];

        return ['lines' => $lines, 'shipping' => $shipping, 'total' => round(array_sum(array_column($lines, 'cost')) + $shipping, 2)];
    }
}
