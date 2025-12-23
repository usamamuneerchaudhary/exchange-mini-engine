<?php

namespace Tests\Feature;

use App\Events\OrderMatched;
use App\Jobs\MatchOrderJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Services\OrderMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_order_matches_with_sell_order_at_lower_price(): void
    {
        Event::fake();

        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create(['balance' => 0]);
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.2,
        ]);

        // Create sell order at 50000
        $sellOrder = Order::factory()->sell()->open()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.2,
        ]);

        // Lock balance for buy order
        $buyer->balance -= 50000.00 * 0.2; // 10000
        $buyer->save();

        // Create buy order at 51000
        $buyOrder = Order::factory()->buy()->open()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'price' => 51000.00,
            'amount' => 0.2,
        ]);

        $matchingService = new OrderMatchingService;
        $trade = $matchingService->matchOrder($buyOrder);

        $this->assertNotNull($trade);
        $this->assertInstanceOf(Trade::class, $trade);

        // Verify trade details
        $this->assertEquals($buyOrder->id, $trade->buy_order_id);
        $this->assertEquals($sellOrder->id, $trade->sell_order_id);
        $this->assertEquals('BTC', $trade->symbol);
        $this->assertEquals(50000.00, $trade->price);
        $this->assertEquals(0.2, $trade->amount);

        // Verify commission
        $expectedCommission = 50000.00 * 0.2 * 0.015; // 150
        $this->assertEquals($expectedCommission, $trade->commission);

        // Verify orders are filled
        $buyOrder->refresh();
        $sellOrder->refresh();
        $this->assertEquals(Order::STATUS_FILLED, $buyOrder->status);
        $this->assertEquals(Order::STATUS_FILLED, $sellOrder->status);

        // Verify buyer received asset
        $buyerAsset = Asset::firstOrCreate([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
        ]);

        $expectedBuyerAmount = 0.2 - ($expectedCommission / 50000.00); // 0.2 - 0.003 = 0.197
        $this->assertEquals($expectedBuyerAmount, $buyerAsset->amount);

        // Verify seller received USD
        $seller->refresh();
        $expectedSellerUSD = 50000.00 * 0.2 - $expectedCommission; // 10000 - 150 = 9850
        $this->assertEquals($expectedSellerUSD, $seller->balance);

        // Verify buyer balance was deducted
        $buyer->refresh();
        $this->assertEquals(0, $buyer->balance); // 10000 - 10000 = 0

        Event::assertDispatched(OrderMatched::class);
    }

    public function test_sell_order_matches_with_buy_order_at_higher_price(): void
    {
        Event::fake();

        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create(['balance' => 0]);
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.2,
        ]);

        // Create buy order at 51000
        $buyOrder = Order::factory()->buy()->open()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'price' => 51000.00,
            'amount' => 0.2,
        ]);

        // Lock balance for buy order
        $buyer->balance -= 51000.00 * 0.2; // 10200
        $buyer->save();

        // Create sell order at 50000
        $sellOrder = Order::factory()->sell()->open()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.2,
        ]);

        $matchingService = new OrderMatchingService;
        $trade = $matchingService->matchOrder($sellOrder);

        $this->assertNotNull($trade);
        $this->assertEquals($buyOrder->id, $trade->buy_order_id);
        $this->assertEquals($sellOrder->id, $trade->sell_order_id);
        $this->assertEquals(50000.00, $trade->price);

        Event::assertDispatched(OrderMatched::class);
    }

    public function test_buy_order_does_not_match_when_sell_price_too_high(): void
    {
        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create(['balance' => 0]);
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.2,
        ]);

        // Create sell order at 51000
        Order::factory()->sell()->open()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'price' => 51000.00,
            'amount' => 0.2,
        ]);

        // Create buy order at 50000
        $buyOrder = Order::factory()->buy()->open()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.2,
        ]);

        $matchingService = new OrderMatchingService;
        $trade = $matchingService->matchOrder($buyOrder);

        $this->assertNull($trade);

        // Verify order remains open
        $buyOrder->refresh();
        $this->assertEquals(Order::STATUS_OPEN, $buyOrder->status);
    }

    public function test_sell_order_does_not_match_when_buy_price_too_low(): void
    {
        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create(['balance' => 0]);
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.2,
        ]);

        // Create buy order at 50000
        Order::factory()->buy()->open()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.2,
        ]);

        // Create sell order at 51000
        $sellOrder = Order::factory()->sell()->open()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'price' => 51000.00,
            'amount' => 0.2,
        ]);

        $matchingService = new OrderMatchingService;
        $trade = $matchingService->matchOrder($sellOrder);

        $this->assertNull($trade);

        // Verify order remains open
        $sellOrder->refresh();
        $this->assertEquals(Order::STATUS_OPEN, $sellOrder->status);
    }

    public function test_commission_is_deducted_from_seller(): void
    {
        Event::fake();

        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create(['balance' => 0]);
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.1,
        ]);

        $sellOrder = Order::factory()->sell()->open()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.1,
        ]);

        $buyOrder = Order::factory()->buy()->open()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'price' => 51000.00,
            'amount' => 0.1,
        ]);

        // Lock buyer balance
        $buyer->balance -= 51000.00 * 0.1;
        $buyer->save();

        $matchingService = new OrderMatchingService;
        $trade = $matchingService->matchOrder($buyOrder);

        $this->assertNotNull($trade);

        // Verify commission
        $expectedCommission = 50000.00 * 0.1 * 0.015; // 75
        $this->assertEquals($expectedCommission, $trade->commission);

        // Verify seller received USD minus commission
        $seller->refresh();
        $expectedSellerUSD = 50000.00 * 0.1 - $expectedCommission; // 5000 - 75 = 4925
        $this->assertEquals($expectedSellerUSD, $seller->balance);
    }

    public function test_match_order_job_processes_order(): void
    {
        Queue::fake();
        Event::fake();

        $buyer = User::factory()->create(['balance' => 10000.00]);
        $seller = User::factory()->create(['balance' => 0]);
        $sellerAsset = Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.2,
        ]);

        $sellOrder = Order::factory()->sell()->open()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.2,
        ]);

        $buyOrder = Order::factory()->buy()->open()->create([
            'user_id' => $buyer->id,
            'symbol' => 'BTC',
            'price' => 51000.00,
            'amount' => 0.2,
        ]);

        // Lock buyer balance
        $buyer->balance -= 51000.00 * 0.2;
        $buyer->save();

        // Process the job
        $job = new MatchOrderJob($buyOrder->id);
        $job->handle(new \App\Services\OrderMatchingService);

        // Verify trade was created
        $this->assertDatabaseHas('trades', [
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
        ]);

        Event::assertDispatched(OrderMatched::class);
    }
}
