<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rettifica manuale dell'admin ai compensi di un dealer in un mese (importo in centesimi, anche negativo). */
class CommissionAdjustment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['month' => 'date:Y-m-d', 'amount_cents' => 'integer'];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
