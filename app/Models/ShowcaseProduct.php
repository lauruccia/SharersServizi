<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowcaseProduct extends Model
{
    protected $guarded = [];

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('uploads/'.$this->photo_path) : null;
    }
}
