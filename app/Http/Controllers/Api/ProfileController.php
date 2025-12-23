<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();

        $assets = $user->assets()->get()->map(function ($asset) {
            return [
                'symbol' => $asset->symbol,
                'amount' => $asset->amount,
                'locked_amount' => $asset->locked_amount,
                'available' => $asset->available_amount,
            ];
        });

        $marketPrices = [];
        foreach (['BTC', 'ETH'] as $symbol) {
            $lastTrade = Trade::where('symbol', $symbol)
                ->orderBy('created_at', 'desc')
                ->first();

            $marketPrices[$symbol] = $lastTrade ? (float) $lastTrade->price : null;
        }

        return response()->json([
            'balance' => (float) $user->balance,
            'assets' => $assets,
            'market_prices' => $marketPrices,
        ]);
    }
}
