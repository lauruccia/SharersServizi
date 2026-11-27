<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const TYPES = [
        'optima' => 'Ordine Optima',
        'altri_operatori' => 'Ordine SIM altri operatori',
        'promo' => 'Ordine Promo Dedicate',
        'generico' => 'Ordine',
    ];

    public const PAYMENT_METHODS = ['bonifico' => 'Bonifico bancario', 'carta' => 'Carta di credito'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'paid_at' => 'datetime',
            'emailed_at' => 'datetime',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Ordine';
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'pagato';
    }

    /** Ordini visibili all'utente: i propri e quelli di chi sta sotto di lui nella gerarchia. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            User::ROLE_SUPERADMIN, User::ROLE_ADMIN => $query,
            User::ROLE_AREA_HEAD => $query->whereIn('agent_id', Agent::query()->visibleTo($user)->select('id')),
            User::ROLE_AGENT => $query->where('agent_id', $user->agent?->id ?? 0),
            default => $query->where('dealer_id', $user->dealer?->id ?? 0),
        };
    }

    public function visibleTo(User $user): bool
    {
        return static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }
}
