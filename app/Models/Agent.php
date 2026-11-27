<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areaHead(): BelongsTo
    {
        return $this->belongsTo(AreaHead::class);
    }

    /** Agenti che l'utente può vedere: tutti (back office), i propri (capoarea), se stesso (agente). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            User::ROLE_SUPERADMIN, User::ROLE_ADMIN => $query,
            User::ROLE_AREA_HEAD => $query->where('area_head_id', $user->areaHead?->id ?? 0),
            User::ROLE_AGENT => $query->whereKey($user->agent?->id ?? 0),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** Il portafoglio: i dealer assegnati a questo agente. */
    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
