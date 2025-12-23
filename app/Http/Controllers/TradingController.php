<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradingController extends Controller
{

    public function __invoke(Request $request)
    {
        $page = $request->query('page', 1);

        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(5, ['*'], 'page', $page)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side,
                    'price' => $order->price,
                    'amount' => $order->amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            })
            ->withQueryString();

        return Inertia::render('Trading', [
            'orders' => $orders,
        ]);
    }

}
