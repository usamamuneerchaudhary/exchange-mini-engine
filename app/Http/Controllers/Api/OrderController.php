<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCancelled;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Jobs\MatchOrderJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $symbol = request()->query('symbol');

        $query = Order::query()->open();

        if ($symbol) {
            $query->forSymbol($symbol);
        }

        $orders = $query->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side,
                    'price' => $order->price,
                    'amount' => $order->amount,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $user = User::lockForUpdate()->find($user->id);

            if ($request->side === Order::SIDE_BUY) {
                $requiredBalance = $request->price * $request->amount;

                if ($user->balance < $requiredBalance) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => [
                            'amount' => ['Insufficient balance.'],
                        ],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $user->balance -= $requiredBalance;
                $user->save();
            } else {
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $request->symbol)
                    ->lockForUpdate()
                    ->first();

                if (! $asset || $asset->amount < $request->amount) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => [
                            'amount' => ['Insufficient asset balance.'],
                        ],
                    ], 422);
                }

                $asset->amount -= $request->amount;
                $asset->locked_amount += $request->amount;
                $asset->save();
            }

            $order = Order::create([
                'user_id' => $user->id,
                'symbol' => $request->symbol,
                'side' => $request->side,
                'price' => $request->price,
                'amount' => $request->amount,
                'status' => Order::STATUS_OPEN,
            ]);

            MatchOrderJob::dispatch($order->id);

            session()->flash('success', 'Order placed successfully.');

            return response()->json([
                'message' => 'Order created successfully.',
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side,
                    'price' => $order->price,
                    'amount' => $order->amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ],
            ], Response::HTTP_CREATED);
        });
    }

    public function myOrders(): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(5)
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
            });

        return response()->json($orders);
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        return DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if (! $order->isOpen()) {
                session()->flash('error', 'Order cannot be cancelled.');

                return response()->json([
                    'message' => 'Order cannot be cancelled.',
                    'success' => false,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::lockForUpdate()->findOrFail($order->user_id);

            if ($order->side === Order::SIDE_BUY) {
                $lockedBalance = $order->price * $order->amount;
                $user->balance += $lockedBalance;
                $user->save();
            } else {
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->firstOrFail();

                $asset->locked_amount -= $order->amount;
                $asset->amount += $order->amount;
                $asset->save();
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            event(new OrderCancelled($order));

            session()->flash('success', 'Order cancelled successfully.');

            return response()->json([
                'message' => 'Order cancelled successfully.',
                'success' => true,
            ]);
        });
    }
}
