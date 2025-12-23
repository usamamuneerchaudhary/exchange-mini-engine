<?php

namespace Tests\Feature;

use App\Events\OrderCancelled;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_cancel_their_own_open_buy_order(): void
    {
        Event::fake();

        $user = User::factory()->create(['balance' => 10000.00]);
        $order = Order::factory()->buy()->open()->create([
            'user_id' => $user->id,
            'price' => 50000.00,
            'amount' => 0.1,
        ]);

        $lockedBalance = $order->price * $order->amount;
        $user->balance -= $lockedBalance;
        $user->save();

        $initialBalance = $user->balance;

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Order cancelled successfully.',
            'success' => true,
        ]);

        // Verify order is cancelled
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);

        // Verify balance is refunded
        $user->refresh();
        $this->assertEquals($initialBalance + $lockedBalance, $user->balance);

        // Verify event was broadcast
        Event::assertDispatched(OrderCancelled::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_user_can_cancel_their_own_open_sell_order(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 0.0,
            'locked_amount' => 0.5,
        ]);

        $order = Order::factory()->sell()->open()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'price' => 50000.00,
            'amount' => 0.5,
        ]);

        $initialLocked = $asset->locked_amount;
        $initialAvailable = $asset->amount;

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Order cancelled successfully.',
            'success' => true,
        ]);

        // Verify order is cancelled
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);

        // Verify asset is unlocked
        $asset->refresh();
        $this->assertEquals($initialLocked - $order->amount, $asset->locked_amount);
        $this->assertEquals($initialAvailable + $order->amount, $asset->amount);

        // Verify event was broadcast
        Event::assertDispatched(OrderCancelled::class);
    }

    public function test_user_cannot_cancel_order_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->open()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_user_cannot_cancel_already_filled_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->filled()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Order cannot be cancelled.',
            'success' => false,
        ]);

        // Verify order status unchanged
        $order->refresh();
        $this->assertEquals(Order::STATUS_FILLED, $order->status);
    }

    public function test_user_cannot_cancel_already_cancelled_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->cancelled()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Order cannot be cancelled.',
            'success' => false,
        ]);
    }

    public function test_cancellation_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(401);
    }
}
