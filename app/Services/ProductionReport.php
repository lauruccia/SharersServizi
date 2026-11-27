<?php

namespace App\Services;

use App\Models\CommissionAdjustment;
use App\Models\CommissionPlan;
use App\Models\Dealer;
use App\Models\ProductionRow;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Conteggi e compensi per dealer in un periodo, solo per i dealer che l'utente può vedere (scope visibleTo).
 * I compensi si calcolano mese per mese con il piano in vigore in quel mese e poi si sommano.
 */
class ProductionReport
{
    public const GROUPS = [
        'dealer' => 'Dealer',
        'agent' => 'Agente',
        'areahead' => 'Capoarea',
        'ambassador' => 'Ambassador',
        'area' => 'Area',
    ];

    public function __construct(private readonly EarningsCalculator $calculator) {}

    /**
     * @return Collection<int, array{dealer: Dealer, metrics: array, earnings: array, months: array}>
     */
    public function build(User $user, DateRange $range): Collection
    {
        $rows = ProductionRow::query()
            ->whereIn('dealer_id', Dealer::query()->visibleTo($user)->select('id'))
            ->whereBetween('signed_at', [$range->from->toDateString(), $range->to->toDateString()])
            ->get()
            ->groupBy('dealer_id');

        $adjustments = CommissionAdjustment::query()
            ->whereIn('dealer_id', Dealer::query()->visibleTo($user)->select('id'))
            ->whereBetween('month', [$range->from->copy()->startOfMonth()->toDateString(), $range->to->toDateString()])
            ->get()->groupBy('dealer_id');

        $dealers = Dealer::query()->with(['agent.user', 'agent.areaHead.user', 'area', 'ambassador'])
            ->whereIn('id', $rows->keys()->merge($adjustments->keys())->unique())->get()->keyBy('id');
        foreach ($adjustments->keys() as $id) {
            $rows[$id] ??= collect();
        }

        $lines = collect();
        foreach ($rows as $dealerId => $dealerRows) {
            $line = ['dealer' => $dealers[$dealerId], 'metrics' => array_fill_keys(EarningsCalculator::METRICS, 0),
                'earnings' => array_fill_keys(EarningsCalculator::EARNINGS, 0), 'months' => []];

            foreach ($dealerRows->groupBy(fn ($r) => $r->signed_month->format('Y-m')) as $month => $monthRows) {
                $plan = CommissionPlan::forMonth($monthRows->first()->signed_month);
                $res = $this->calculator->month($monthRows, $plan);
                $line['months'][$month] = $res;
                foreach ($res['metrics'] as $k => $v) {
                    $line['metrics'][$k] += $v;
                }
                foreach ($res['earnings'] as $k => $v) {
                    $line['earnings'][$k] += $v;
                }
            }
            foreach ($adjustments->get($dealerId, collect()) as $adj) {
                $key = $adj->month->format('Y-m');
                $line['months'][$key] ??= ['metrics' => array_fill_keys(EarningsCalculator::METRICS, 0), 'earnings' => array_fill_keys(EarningsCalculator::EARNINGS, 0)];
                $line['months'][$key]['earnings']['adjust'] += $adj->amount_cents;
                $line['months'][$key]['earnings']['total'] += $adj->amount_cents;
                $line['earnings']['adjust'] += $adj->amount_cents;
                $line['earnings']['total'] += $adj->amount_cents;
            }
            ksort($line['months']);
            $lines->put($dealerId, $line + ['derived' => self::derived($line['metrics'])]);
        }

        return $lines->sortBy(fn ($l) => mb_strtolower($l['dealer']->business_name))->values();
    }

    /** Centesimi → "1.234,50 €". */
    public static function eur(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }

    /** Attivazioni totali (SIM + luce + gas + fibra) e portabilità (SIM + fibra). */
    public static function derived(array $m): array
    {
        return [
            'activations' => $m['sim'] + $m['luce'] + $m['gas'] + $m['fibra'],
            'mnp' => $m['sim_mnp'] + $m['fibra_mnp'],
            'luce_gas' => $m['luce'] + $m['gas'],
            'pending' => $m['pending_sim'] + $m['pending_luce'] + $m['pending_gas'] + $m['pending_fibra'],
        ];
    }

    /**
     * Raggruppa le righe per agente, capoarea, ambassador, area (o dealer) e somma conteggi e compensi.
     *
     * @return Collection<int, array{label: string, dealers: int, metrics: array, earnings: array, derived: array, dealer?: Dealer}>
     */
    public function groupBy(Collection $lines, string $by): Collection
    {
        $key = fn (array $l): array => match ($by) {
            'agent' => [$l['dealer']->agent_id ?? 0, $l['dealer']->agent?->user->name ?? 'Senza agente'],
            'areahead' => [$l['dealer']->agent?->area_head_id ?? 0, $l['dealer']->agent?->areaHead?->user->name ?? 'Senza capoarea'],
            'ambassador' => [$l['dealer']->ambassador_id ?? 0, $l['dealer']->ambassador?->name ?? 'Senza ambassador'],
            'area' => [$l['dealer']->area_id ?? 0, $l['dealer']->area?->name ?? 'Senza area'],
            default => [$l['dealer']->id, $l['dealer']->business_name],
        };

        $groups = [];
        foreach ($lines as $l) {
            [$id, $label] = $key($l);
            $g = &$groups[$id];
            $g ??= ['label' => $label, 'dealers' => 0, 'metrics' => array_fill_keys(EarningsCalculator::METRICS, 0),
                'earnings' => array_fill_keys(EarningsCalculator::EARNINGS, 0), 'dealer' => $by === 'dealer' ? $l['dealer'] : null];
            $g['dealers']++;
            foreach ($l['metrics'] as $k => $v) {
                $g['metrics'][$k] += $v;
            }
            foreach ($l['earnings'] as $k => $v) {
                $g['earnings'][$k] += $v;
            }
            unset($g);
        }

        return collect($groups)->map(fn ($g) => $g + ['derived' => self::derived($g['metrics'])])
            ->sortBy(fn ($g) => mb_strtolower($g['label']))->values();
    }

    /** Totale di tutte le righe. */
    public function totals(Collection $lines): array
    {
        $t = ['dealers' => $lines->count(), 'metrics' => array_fill_keys(EarningsCalculator::METRICS, 0), 'earnings' => array_fill_keys(EarningsCalculator::EARNINGS, 0)];
        foreach ($lines as $l) {
            foreach ($l['metrics'] as $k => $v) {
                $t['metrics'][$k] += $v;
            }
            foreach ($l['earnings'] as $k => $v) {
                $t['earnings'][$k] += $v;
            }
        }

        return $t + ['derived' => self::derived($t['metrics'])];
    }
}
