<?php

namespace App\Services;

use App\Models\CommissionPlan;
use App\Models\ProductionRow;
use Illuminate\Support\Collection;

/**
 * Conteggi e compensi di UN dealer in UN mese (docs/03, "Calcolo dei guadagni").
 * Gli scaglioni sono retroattivi: raggiunto lo scaglione, la tariffa vale per tutte le pratiche del mese.
 * Importi in centesimi (interi).
 */
class EarningsCalculator
{
    public const METRICS = [
        'sim', 'sim_mnp', 'luce', 'gas', 'fibra', 'fibra_mnp', 'other',
        'pending_sim', 'pending_luce', 'pending_gas', 'pending_fibra',
    ];

    public const EARNINGS = ['sim', 'fibra', 'energy', 'adjust', 'total'];

    /**
     * @param  Collection<int, ProductionRow>  $rows  righe di un solo dealer e di un solo mese
     * @return array{metrics: array<string,int>, earnings: array<string,int>}
     */
    public function month(Collection $rows, CommissionPlan $plan): array
    {
        $m = array_fill_keys(self::METRICS, 0);
        $tiers = ['sim_495' => ['qty' => 0, 'mnp' => 0], 'sim_695' => ['qty' => 0, 'mnp' => 0]];
        $fibra = ['qty' => 0, 'mnp' => 0];
        $energy = [];
        $codes = array_map('strtoupper', (array) $plan->param('sim_695_codes'));
        $payPending = (bool) $plan->param('count_pending');

        foreach ($rows as $row) {
            $outcome = $row->effectiveOutcome();
            $active = $outcome === ProductionRow::OUTCOME_ACTIVE;
            $pending = $outcome === ProductionRow::OUTCOME_PENDING;
            $pays = $active || ($pending && $payPending);

            switch ($row->service_type) {
                case 'sim':
                    $qty = (int) $row->sims_sold;
                    $mnp = min((int) $row->sims_portability, $qty);
                    if ($active) {
                        $m['sim'] += $qty;
                        $m['sim_mnp'] += $mnp;
                    } elseif ($pending) {
                        $m['pending_sim'] += $qty;
                    }
                    if ($pays) {
                        $tier = in_array(strtoupper((string) $row->offer_code), $codes, true) ? 'sim_695' : 'sim_495';
                        $tiers[$tier]['qty'] += $qty;
                        $tiers[$tier]['mnp'] += $mnp;
                    }
                    break;
                case 'luce':
                case 'gas':
                    if ($active) {
                        $m[$row->service_type]++;
                    } elseif ($pending) {
                        $m['pending_'.$row->service_type]++;
                    }
                    if ($pays) {
                        $energy[] = $row;
                    }
                    break;
                case 'fibra':
                    $isMnp = $this->fibraIsMnp($row, (string) $plan->param('fibra_mnp'));
                    if ($active) {
                        $m['fibra']++;
                        if ($isMnp) {
                            $m['fibra_mnp']++;
                        }
                    } elseif ($pending) {
                        $m['pending_fibra']++;
                    }
                    if ($pays) {
                        $fibra['qty']++;
                        if ($isMnp) {
                            $fibra['mnp']++;
                        }
                    }
                    break;
                case 'voip': // opzione dell'ADSL: non si conteggia
                    break;
                default:
                    if ($active) {
                        $m['other']++;
                    }
            }
        }

        $e = array_fill_keys(self::EARNINGS, 0);

        // SIM: scaglione sul totale SIM del dealer (o per offerta, se così è impostato).
        $simTotal = $tiers['sim_495']['qty'] + $tiers['sim_695']['qty'];
        foreach ($tiers as $product => $t) {
            $n = $plan->param('sim_scaglione') === 'per_offerta' ? $t['qty'] : $simTotal;
            if ($t['qty'] > 0 && ($rule = $plan->rule($product, $n))) {
                $e['sim'] += $t['qty'] * $this->cents($rule->base_amount) + $t['mnp'] * $this->cents($rule->mnp_amount);
            }
        }

        // Fibra: attivazione per ogni pratica, più la maggiorazione per quelle con portabilità.
        if ($fibra['qty'] > 0 && ($rule = $plan->rule('fibra', $fibra['qty']))) {
            $e['fibra'] += $fibra['qty'] * $this->cents($rule->base_amount) + $fibra['mnp'] * $this->cents($rule->mnp_amount);
        }

        // Energy: scaglione sul totale pratiche luce+gas; base per consumer/business più bonus SDD ed email.
        $n = count($energy);
        foreach ($energy as $row) {
            $rule = $plan->rule($row->is_consumer ? 'energy_consumer' : 'energy_business', $n);
            if (! $rule) {
                continue;
            }
            $e['energy'] += $this->cents($rule->base_amount)
                + (strcasecmp((string) $row->payment_method, 'SDD') === 0 ? $this->cents($plan->param('sdd_bonus')) : 0)
                + (strcasecmp((string) $row->invoice_delivery, 'Email') === 0 ? $this->cents($plan->param('email_bonus')) : 0);
        }

        $e['total'] = $e['sim'] + $e['fibra'] + $e['energy'];

        return ['metrics' => $m, 'earnings' => $e];
    }

    private function fibraIsMnp(ProductionRow $row, string $source): bool
    {
        if ($row->fibra_mnp_force !== null) {
            return (bool) $row->fibra_mnp_force; // deciso a mano dall'admin
        }

        return $source === 'portabilita'
            ? (int) $row->sims_portability > 0
            : strcasecmp((string) $row->activation_type, 'MIGRASOLODATI') === 0;
    }

    private function cents(mixed $euro): int
    {
        return (int) round(((float) $euro) * 100);
    }
}
