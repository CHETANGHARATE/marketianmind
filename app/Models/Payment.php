<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'user_id',
    'course_id',
    'razorpay_payment_id',
    'razorpay_order_id',
    'amount',
    'currency',
    'status',
    'method',
    'captured',
    'paid_at',
    'failure_code',
    'failure_description',
    'metadata',
])]
class Payment extends Model
{
    use HasFactory;

    /**
     * Default model attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'INR',
        'status' => 'created',
        'captured' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'captured' => 'boolean',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isCaptured(): bool
    {
        return $this->captured || $this->status === PaymentStatus::CAPTURED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function amountInRupees(): float
    {
        return round($this->amount / 100, 2);
    }

    public function formattedAmount(): string
    {
        return '₹' . number_format($this->amountInRupees(), 2);
    }
}