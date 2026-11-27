<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registry_completed_at' => 'datetime',
            'registry_submitted_at' => 'datetime',
            'privacy_confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class);
    }

    public function productionRows(): HasMany
    {
        return $this->hasMany(ProductionRow::class);
    }

    /** Dealer che l'utente può vedere: tutti i livelli sotto di lui, mai quelli sopra. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            User::ROLE_SUPERADMIN, User::ROLE_ADMIN => $query,
            User::ROLE_AREA_HEAD => $query->whereIn('agent_id', Agent::query()->visibleTo($user)->select('id')),
            User::ROLE_AGENT => $query->where('agent_id', $user->agent?->id ?? 0),
            default => $query->whereKey($user->dealer?->id ?? 0),
        };
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DealerDocument::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public const BILLING_REGIMES = [
        'standard' => 'di non trovarsi nelle condizioni di poter beneficiare di esenzioni o dell\'applicazione di aliquote agevolate',
        'ritenuta_ridotta' => 'di trovarsi nella condizione di poter beneficiare della ritenuta d\'acconto nella misura ridotta sulle provvigioni: aliquota 23% sul 20% dei compensi',
        'forfettario' => 'di essere in regime fiscale forfettario',
    ];

    /** Stato dell'anagrafica fiscale: da_completare | in_verifica | completa. */
    public function registryState(): string
    {
        if ($this->registry_completed_at) {
            return 'completa';
        }

        return $this->registry_submitted_at ? 'in_verifica' : 'da_completare';
    }

    /** Anagrafica fiscale completa: condizione per poter ordinare. */
    public function canOrder(): bool
    {
        return $this->registry_completed_at !== null;
    }
}
