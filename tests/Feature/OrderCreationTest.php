<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_buy_order_with_sufficient_balance(): void
    {
        Queue::fake();

        $user = User::factory()->create(['balance' => 10000.00]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.1,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'success',
            'order' => ['id', 'symbol', 'side', 'price', 'amount', 'status'],
        ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.1,
            'status' => Order::STATUS_OPEN,
        ]);

        // Verify balance was locked
        $user->refresh();
        $expectedLocked = 50000.00 * 0.1; // 5000
        $this->assertEquals(10000.00 - $expectedLocked, $user->balance);

        // Verify job was dispatched
        Queue::assertPushed(\App\Jobs\MatchOrderJob::class);
    }

    public function test_user_cannot_create_buy_order_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.1, // requires 5000, but only has 1000
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_user_can_create_sell_order_with_sufficient_asset(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 1.0,
            'locked_amount' => 0.0,
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 50000.00,
            'amount' => 0.5,
        ]);

        $response->assertStatus(201);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 50000.00,
            'amount' => 0.5,
            'status' => Order::STATUS_OPEN,
        ]);

        // Verify asset was locked
        $asset->refresh();
        $this->assertEquals(0.5, $asset->amount); // 1.0 - 0.5
        $this->assertEquals(0.5, $asset->locked_amount);

        Queue::assertPushed(\App\Jobs\MatchOrderJob::class);
    }

    public function test_user_cannot_create_sell_order_with_insufficient_asset(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 0.1,
            'locked_amount' => 0.0,
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 50000.00,
            'amount' => 0.5, // Only has 0.1
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_order_creation_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.1,
        ]);

        $response->assertStatus(401);
    }

    public function test_order_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
    }

    public function test_order_creation_validates_symbol(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'INVALID',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => 0.1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['symbol']);
    }

    public function test_order_creation_validates_side(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'invalid',
            'price' => 50000.00,
            'amount' => 0.1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['side']);
    }

    public function test_order_creation_validates_price_is_positive(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => -100,
            'amount' => 0.1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    public function test_order_creation_validates_amount_is_positive(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000.00,
            'amount' => -0.1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }
}
