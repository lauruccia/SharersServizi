<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'price' => 'decimal:2'];
    }

    /** Solo le offerte in corso: alla scadenza spariscono da sole, senza bisogno di cron. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->starts_at <= now() && $this->ends_at > now();
    }

    public function label(): string
    {
        return $this->duration_hours >= 168 ? 'Offerta 1 settimana' : 'Offerta '.$this->duration_hours.'h';
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('uploads/'.$this->photo_path) : null;
    }
}
