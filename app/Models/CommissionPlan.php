<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** Piano compensi dealer. Vale dal mese `valid_from` fino al piano successivo: i mesi passati restano allo storico. */
class CommissionPlan extends Model
{
    protected $guarded = [];

    public const PRODUCTS = [
        'sim_495' => 'SIM 4,95',
        'sim_695' => 'SIM 6,95',
        'fibra' => 'Fibra',
        'energy_consumer' => 'Energy consumer',
        'energy_business' => 'Energy business',
    ];

    public const DEFAULT_PARAMS = [
        'sdd_bonus' => 15,
        'email_bonus' => 5,
        // "totale": lo scaglione SIM si conta sul totale SIM del dealer nel mese; "per_offerta": a parte per 4,95 e 6,95.
        'sim_scaglione' => 'totale',
        // Come si riconosce la fibra con portabilità: "tipo_attivazione" (MIGRASOLODATI) o "portabilita" (colonna nSimPortabilita).
        'fibra_mnp' => 'tipo_attivazione',
        // Codici offerta SIM a 6,95; tutti gli altri sono 4,95.
        'sim_695_codes' => ['SMOB_5G_DE_0926'],
        // Se true anche i contratti "in lavorazione" entrano nei compensi.
        'count_pending' => false,
    ];

    /** [prodotto, da, a, attivazione, aggiunta MNP]. Per l'energy "base" = compenso base e MNP non si usa. */
    public const DEFAULT_RULES = [
        ['sim_495', 1, 4, 2, 3], ['sim_495', 5, 9, 7, 5], ['sim_495', 10, 24, 10, 8], ['sim_495', 25, null, 12, 10],
        ['sim_695', 1, 4, 7, 8], ['sim_695', 5, 9, 9, 10], ['sim_695', 10, 24, 11, 12], ['sim_695', 25, null, 13, 20],
        ['fibra', 1, 4, 50, 70], ['fibra', 5, null, 130, 150],
        ['energy_consumer', 1, 4, 40, 0], ['energy_consumer', 5, 9, 65, 0], ['energy_consumer', 10, 19, 70, 0], ['energy_consumer', 20, null, 75, 0],
        ['energy_business', 1, 4, 60, 0], ['energy_business', 5, 9, 80, 0], ['energy_business', 10, 19, 85, 0], ['energy_business', 20, null, 90, 0],
    ];

    protected function casts(): array
    {
        return ['valid_from' => 'date:Y-m-d', 'params' => 'array'];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CommissionRule::class)->orderBy('product')->orderBy('min_qty');
    }

    public function param(string $key): mixed
    {
        return ($this->params ?? [])[$key] ?? (self::DEFAULT_PARAMS[$key] ?? null);
    }

    /** Il piano in vigore nel mese dato (l'ultimo con valid_from <= mese); se il mese è precedente a tutti, il primo. */
    public static function forMonth(Carbon $month): self
    {
        $day = $month->copy()->startOfMonth()->toDateString();
        $plan = static::query()->where('valid_from', '<=', $day)->orderByDesc('valid_from')->orderByDesc('id')->first()
            ?? static::query()->orderBy('valid_from')->first();

        abort_if(! $plan, 500, 'Nessun piano compensi configurato.');

        return $plan->loadMissing('rules');
    }

    /** Regola per prodotto e quantità mensile; oltre l'ultimo scaglione resta l'ultimo. Null se qty = 0. */
    public function rule(string $product, int $qty): ?CommissionRule
    {
        if ($qty <= 0) {
            return null;
        }
        $rules = $this->rules->where('product', $product)->sortBy('min_qty')->values();
        $match = $rules->first(fn (CommissionRule $r) => $qty >= $r->min_qty && ($r->max_qty === null || $qty <= $r->max_qty));

        return $match ?? $rules->last();
    }
}
