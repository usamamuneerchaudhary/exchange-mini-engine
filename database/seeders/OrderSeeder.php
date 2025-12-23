<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usama = User::where('email', 'usama@test.com')->first();
        $butt = User::where('email', 'butt@test.com')->first();
        $ali = User::where('email', 'ali@test.com')->first();

        if (!$usama || !$butt || !$ali) {
            $this->command->warn('Users not found. Please run UserSeeder first.');

            return;
        }

        DB::transaction(function () use ($usama, $butt, $ali) {
            // usama wants to sell BTC at $95,000
            if ($usama) {
                $usamaBtc = Asset::where('user_id', $usama->id)->where('symbol', 'BTC')->first();
                if ($usamaBtc && $usamaBtc->amount >= 0.1) {
                    Order::create([
                        'user_id' => $usama->id,
                        'symbol' => 'BTC',
                        'side' => Order::SIDE_SELL,
                        'price' => 95000.00,
                        'amount' => 0.1,
                        'status' => Order::STATUS_OPEN,
                    ]);

                    $usamaBtc->amount -= 0.1;
                    $usamaBtc->locked_amount += 0.1;
                    $usamaBtc->save();
                }
            }

            // butt wants to sell BTC at $96,000
            if ($butt) {
                $buttBtc = Asset::where('user_id', $butt->id)->where('symbol', 'BTC')->first();
                if ($buttBtc && $buttBtc->amount >= 0.05) {
                    Order::create([
                        'user_id' => $butt->id,
                        'symbol' => 'BTC',
                        'side' => Order::SIDE_SELL,
                        'price' => 96000.00,
                        'amount' => 0.05,
                        'status' => Order::STATUS_OPEN,
                    ]);

                    $buttBtc->amount -= 0.05;
                    $buttBtc->locked_amount += 0.05;
                    $buttBtc->save();
                }
            }

            // ali wants to sell BTC at $97,000
            if ($ali) {
                $aliBtc = Asset::where('user_id', $ali->id)->where('symbol', 'BTC')->first();
                if ($aliBtc && $aliBtc->amount >= 0.02) {
                    Order::create([
                        'user_id' => $ali->id,
                        'symbol' => 'BTC',
                        'side' => Order::SIDE_SELL,
                        'price' => 97000.00,
                        'amount' => 0.02,
                        'status' => Order::STATUS_OPEN,
                    ]);

                    $aliBtc->amount -= 0.02;
                    $aliBtc->locked_amount += 0.02;
                    $aliBtc->save();
                }
            }
        });

        // Create some buy orders for BTC
        DB::transaction(function () use ($usama, $butt) {
            // usama wants to buy BTC at $94,000
            if ($usama && $usama->balance >= 9400) {
                Order::create([
                    'user_id' => $usama->id,
                    'symbol' => 'BTC',
                    'side' => Order::SIDE_BUY,
                    'price' => 94000.00,
                    'amount' => 0.1,
                    'status' => Order::STATUS_OPEN,
                ]);

                $usama->balance -= 9400;
                $usama->save();
            }

            // butt wants to buy BTC at $93,000
            if ($butt && $butt->balance >= 4650) {
                Order::create([
                    'user_id' => $butt->id,
                    'symbol' => 'BTC',
                    'side' => Order::SIDE_BUY,
                    'price' => 93000.00,
                    'amount' => 0.05,
                    'status' => Order::STATUS_OPEN,
                ]);

                $butt->balance -= 4650;
                $butt->save();
            }
        });

        // Create some ETH orders
        DB::transaction(function () use ($usama, $butt) {
            // usama wants to sell ETH at $3,300
            if ($usama) {
                $usamaEth = Asset::where('user_id', $usama->id)->where('symbol', 'ETH')->first();
                if ($usamaEth && $usamaEth->amount >= 2.0) {
                    Order::create([
                        'user_id' => $usama->id,
                        'symbol' => 'ETH',
                        'side' => Order::SIDE_SELL,
                        'price' => 3300.00,
                        'amount' => 2.0,
                        'status' => Order::STATUS_OPEN,
                    ]);

                    $usamaEth->amount -= 2.0;
                    $usamaEth->locked_amount += 2.0;
                    $usamaEth->save();
                }
            }

            // butt wants to buy ETH at $3,100
            if ($butt && $butt->balance >= 3100) {
                Order::create([
                    'user_id' => $butt->id,
                    'symbol' => 'ETH',
                    'side' => Order::SIDE_BUY,
                    'price' => 3100.00,
                    'amount' => 1.0,
                    'status' => Order::STATUS_OPEN,
                ]);

                $butt->balance -= 3100;
                $butt->save();
            }
        });
    }
}
