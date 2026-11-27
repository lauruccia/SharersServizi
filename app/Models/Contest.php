<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Extra gara: classifica dei dealer su un periodo, per ora sulla portabilità. */
class Contest extends Model
{
    protected $guarded = [];

    public const METRICS = [
        'mnp_all' => 'Portabilità totali (SIM + fibra)',
        'mnp_sim' => 'Portabilità SIM',
        'mnp_fibra' => 'Portabilità fibra',
    ];

    protected function casts(): array
    {
        return ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'active' => 'boolean'];
    }

    public function isRunning(): bool
    {
        return $this->active && $this->ends_on->endOfDay()->isFuture();
    }
}
