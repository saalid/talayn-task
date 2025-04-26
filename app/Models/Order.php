<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id', 'type', 'amount', 'remaining_amount',
        'price_per_gram', 'status'
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($order) {
            $order->remaining_amount = $order->amount;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return bool
     */
    public function isBuy(): bool
    {
        return $this->type === 'buy';
    }

    /**
     * @return bool
     */
    public function isSell(): bool
    {
        return $this->type === 'sell';
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === 'open' || $this->status === 'partial';
    }
}
