<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const OPEN = 'open';
    public const ANSWERED = 'answered';
    public const CLOSED = 'closed';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function dealer(): BelongsTo { return $this->belongsTo(Dealer::class); }
    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
    public function messages(): HasMany { return $this->hasMany(TicketMessage::class)->oldest('id'); }

    public function isClosed(): bool { return $this->status === self::CLOSED; }

    public function statusLabel(): string
    {
        return ['open' => 'in attesa di risposta', 'answered' => 'risposto', 'closed' => 'chiusa'][$this->status] ?? $this->status;
    }

    public function categoryLabel(): string
    {
        return config('effepi.ticket_categories')[$this->category] ?? $this->category;
    }

    /** Segnalazioni visibili: le proprie e quelle degli agenti sotto di sé. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            User::ROLE_SUPERADMIN, User::ROLE_ADMIN => $query,
            User::ROLE_AREA_HEAD => $query->where(fn (Builder $q) => $q->where('user_id', $user->id)
                ->orWhereIn('agent_id', Agent::query()->visibleTo($user)->select('id'))),
            User::ROLE_AGENT => $query->where(fn (Builder $q) => $q->where('user_id', $user->id)
                ->orWhere('agent_id', $user->agent?->id ?? 0)),
            default => $query->where('user_id', $user->id),
        };
    }

    public function visibleTo(User $user): bool
    {
        return static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }
}
