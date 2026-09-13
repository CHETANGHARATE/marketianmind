<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'offer_id',
        'product_type',
        'product_id',
    ];

    /**
     * Get the offer associated with this product mapping.
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Get the target course if this mapping is for a course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'product_id');
    }

    /**
     * Get the target bundle if this mapping is for a bundle.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class, 'product_id');
    }
}
