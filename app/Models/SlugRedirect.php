<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlugRedirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'old_path',
        'new_path',
        'status_code',
        'hits',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'hits' => 'integer',
    ];

    public function recordHit(): void
    {
        $this->increment('hits');
    }
}
