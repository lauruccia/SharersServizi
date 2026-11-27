<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationRequest extends Model
{
    public const PENDING = 'in_attesa';

    public const APPROVED = 'approvata';

    public const REJECTED = 'rifiutata';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meeting_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'meeting_manager_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RegistrationMessage::class);
    }

    public function isDealer(): bool
    {
        return $this->profile === 'dealer';
    }

    public function displayName(): string
    {
        return $this->isDealer() ? ($this->business_name ?: $this->name) : $this->name;
    }
}
