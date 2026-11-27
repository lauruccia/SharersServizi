<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Modulo, grafica o video messo a disposizione da Effepi (file caricato o link). */
class Resource extends Model
{
    public const KINDS = [
        'moduli' => 'Moduli',
        'grafica' => 'Grafica e locandine',
        'video' => 'Video',
        'rapicom' => 'Grafiche Rapicom',
    ];

    protected $guarded = [];

    public function href(): ?string
    {
        return $this->file_path ? Storage::disk('uploads')->url($this->file_path) : $this->url;
    }
}
