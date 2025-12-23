<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            if ($user->email === 'usama@test.com') {
                Asset::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'symbol' => 'BTC',
                    ],
                    [
                        'amount' => 1.5, // 1.5 BTC
                        'locked_amount' => 0,
                    ]
                );

                Asset::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'symbol' => 'ETH',
                    ],
                    [
                        'amount' => 10.0, // 10 ETH
                        'locked_amount' => 0,
                    ]
                );
            }

            if ($user->email === 'butt@test.com') {
                Asset::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'symbol' => 'BTC',
                    ],
                    [
                        'amount' => 0.5, // 0.5 BTC
                        'locked_amount' => 0,
                    ]
                );

                Asset::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'symbol' => 'ETH',
                    ],
                    [
                        'amount' => 5.0, // 5 ETH
                        'locked_amount' => 0,
                    ]
                );
            }

            if ($user->email === 'ali@test.com') {
                Asset::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'symbol' => 'BTC',
                    ],
                    [
                        'amount' => 0.25, // 0.25 BTC
                        'locked_amount' => 0,
                    ]
                );
            }
        }
    }
}
