<?php

namespace App\Events;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Trade $trade,
        public User  $buyer,
        public User  $seller
    )
    {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
            new PrivateChannel('user.' . $this->buyer->id),
            new PrivateChannel('user.' . $this->seller->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $buyerAsset = $this->buyer->assets()
            ->where('symbol', $this->trade->symbol)
            ->first();

        $sellerAsset = $this->seller->assets()
            ->where('symbol', $this->trade->symbol)
            ->first();

        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'commission' => $this->trade->commission,
                'created_at' => $this->trade->created_at,
            ],
            'buy_order_id' => $this->trade->buy_order_id,
            'sell_order_id' => $this->trade->sell_order_id,
            'buyer' => [
                'balance' => $this->buyer->balance,
                'asset' => $buyerAsset ? [
                    'symbol' => $buyerAsset->symbol,
                    'amount' => $buyerAsset->amount,
                    'locked_amount' => $buyerAsset->locked_amount,
                ] : null,
            ],
            'seller' => [
                'balance' => $this->seller->balance,
                'asset' => $sellerAsset ? [
                    'symbol' => $sellerAsset->symbol,
                    'amount' => $sellerAsset->amount,
                    'locked_amount' => $sellerAsset->locked_amount,
                ] : null,
            ],
        ];
    }
}
