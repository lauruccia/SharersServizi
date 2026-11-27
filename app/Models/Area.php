<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $guarded = [];

    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class);
    }

    public function ambassadors(): HasMany
    {
        return $this->hasMany(Ambassador::class);
    }
}
