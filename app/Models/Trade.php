<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'symbol',
        'price',
        'amount',
        'commission',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'amount' => 'decimal:8',
            'commission' => 'decimal:2',
        ];
    }

    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }
}
