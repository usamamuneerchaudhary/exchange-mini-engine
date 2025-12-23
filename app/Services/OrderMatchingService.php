<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    private const COMMISSION_RATE = 0.015;

    public function matchOrder(Order $order): ?Trade
    {
        if (! $order->isOpen()) {
            return null;
        }

        $matchingOrder = $this->findMatchingOrder($order);

        if (! $matchingOrder) {
            return null;
        }

        return $this->executeMatch($order, $matchingOrder);
    }

    private function findMatchingOrder(Order $order): ?Order
    {
        if ($order->side === Order::SIDE_BUY) {
            return Order::query()
                ->open()
                ->forSymbol($order->symbol)
                ->sellOrders()
                ->where('price', '<=', $order->price)
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();
        }

        return Order::query()
            ->open()
            ->forSymbol($order->symbol)
            ->buyOrders()
            ->where('price', '>=', $order->price)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();
    }

    private function executeMatch(Order $order1, Order $order2): Trade
    {
        return DB::transaction(function () use ($order1, $order2) {
            $buyOrder = $order1->side === Order::SIDE_BUY ? $order1 : $order2;
            $sellOrder = $order1->side === Order::SIDE_SELL ? $order1 : $order2;

            $buyer = $buyOrder->user()->lockForUpdate()->first();
            $seller = $sellOrder->user()->lockForUpdate()->first();

            // Match at the sell order pric
            $price = $sellOrder->price;
            $amount = $buyOrder->amount;
            $volume = $price * $amount;
            $commission = $volume * self::COMMISSION_RATE;
            $assetCommission = $commission / $price;

            $buyerAsset = Asset::query()
                ->where('user_id', $buyer->id)
                ->where('symbol', $buyOrder->symbol)
                ->lockForUpdate()
                ->first();

            if (! $buyerAsset) {
                $buyerAsset = Asset::create([
                    'user_id' => $buyer->id,
                    'symbol' => $buyOrder->symbol,
                    'amount' => 0,
                    'locked_amount' => 0,
                ]);
            }

            $sellerAsset = Asset::query()
                ->where('user_id', $seller->id)
                ->where('symbol', $sellOrder->symbol)
                ->lockForUpdate()
                ->firstOrFail();

            // commission deducted from seller
            // Buyer receives asset minus commision
            $buyerAsset->amount += ($amount - $assetCommission);
            $buyerAsset->save();


            $seller->balance += ($volume - $commission);
            $seller->save();

            // release sellers locked asset
            $sellerAsset->locked_amount -= $amount;
            $sellerAsset->save();

            $buyOrder->status = Order::STATUS_FILLED;
            $buyOrder->save();

            $sellOrder->status = Order::STATUS_FILLED;
            $sellOrder->save();

            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'symbol' => $buyOrder->symbol,
                'price' => $price,
                'amount' => $amount,
                'commission' => $commission,
            ]);

            event(new OrderMatched($trade, $buyer, $seller));

            return $trade;
        });
    }
}
