<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerDocument extends Model
{
    public const TYPES = [
        'visura' => 'Visura camerale',
        'documento' => 'Documento d\'identità dell\'amministratore',
        'foto_interna' => 'Foto interna del negozio',
        'foto_esterna' => 'Foto esterna del negozio',
    ];

    protected $guarded = [];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
