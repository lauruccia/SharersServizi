<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRow extends Model
{
    protected $guarded = [];

    public const OUTCOME_ACTIVE = 'active';

    public const OUTCOME_PENDING = 'pending';

    public const OUTCOME_EXCLUDED = 'excluded';

    public const OUTCOME_LABELS = [
        self::OUTCOME_ACTIVE => 'Attivo',
        self::OUTCOME_PENDING => 'In lavorazione',
        self::OUTCOME_EXCLUDED => 'Non conteggiato',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'date:Y-m-d', 'signed_month' => 'date:Y-m-d', 'status_at' => 'datetime',
            'is_consumer' => 'boolean', 'force_count' => 'boolean', 'friend_code_insert' => 'boolean', 'sims_paid' => 'boolean',
            'locked' => 'boolean', 'is_manual' => 'boolean', 'fibra_mnp_force' => 'boolean',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /** Esito effettivo: il flag "forza" vince sul calcolo automatico. */
    public function effectiveOutcome(): string
    {
        return match ($this->force_count) {
            true => self::OUTCOME_ACTIVE,
            false => self::OUTCOME_EXCLUDED,
            default => $this->outcome,
        };
    }
}
