<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['base_amount' => 'decimal:2', 'mnp_amount' => 'decimal:2'];
    }

    public function rangeLabel(): string
    {
        return $this->max_qty === null ? $this->min_qty.' e oltre' : $this->min_qty.' – '.$this->max_qty;
    }
}
