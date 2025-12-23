<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 1;

    public const STATUS_FILLED = 2;

    public const STATUS_CANCELLED = 3;

    public const SIDE_BUY = 'buy';

    public const SIDE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'amount' => 'decimal:8',
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeForSymbol(Builder $query, string $symbol): Builder
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeBuyOrders(Builder $query): Builder
    {
        return $query->where('side', self::SIDE_BUY);
    }

    public function scopeSellOrders(Builder $query): Builder
    {
        return $query->where('side', self::SIDE_SELL);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isFilled(): bool
    {
        return $this->status === self::STATUS_FILLED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
