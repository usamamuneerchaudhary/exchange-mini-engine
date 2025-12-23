<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchOrderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OrderMatchingService $matchingService): void
    {
        $order = Order::find($this->orderId);

        if (! $order || ! $order->isOpen()) {
            return;
        }

        $matchingService->matchOrder($order);
    }
}
