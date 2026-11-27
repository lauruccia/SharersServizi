<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Capoarea: gestisce più agenti e, tramite loro, i dealer. */
class AreaHead extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }
}
