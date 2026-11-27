<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'created_at' => 'datetime'];
    }

    /** Registra chi ha fatto cosa (approvazioni, promo, modifiche alla rete). */
    public static function record(string $action, ?Model $subject = null, array $data = []): void
    {
        static::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'data' => $data ?: null,
            'created_at' => now(),
        ]);
    }
}
