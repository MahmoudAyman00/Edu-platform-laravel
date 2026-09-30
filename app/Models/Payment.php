<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'meta' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [PaymentStatus::PAID, PaymentStatus::EXPIRED, PaymentStatus::FAILED], true);
    }

    public function isLive(): bool
    {
        return $this->status === PaymentStatus::PENDING
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
