<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\Trade;
use App\Models\WalletTransaction;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create Users with Wallets
            $users = collect([
                ['name' => 'Akbar', 'email' => 'akbar@test.com'],
                ['name' => 'Ahmad', 'email' => 'ahmad@test.com'],
                ['name' => 'Reza', 'email' => 'reza@test.com'],
            ])->map(function ($userData) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                ]);

                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => rand(50_000_000, 200_000_000),
                ]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'deposit',
                    'amount' => $wallet->balance,
                    'balance_after' => $wallet->balance,
                    'description' => 'Initial deposit',
                ]);

                return $user;
            });

            $akbar = $users->firstWhere('name', 'Akbar');
            $ahmad = $users->firstWhere('name', 'Ahmad');
            $reza = $users->firstWhere('name', 'Reza');

            // 2. Create Buy Orders (Ahmad, Reza)
            $orderAhmad = Order::create([
                'user_id' => $ahmad->id,
                'type' => 'buy',
                'amount' => 2.000,
                'price' => 10_000_000,
                'remaining_amount' => 2.000,
            ]);

            $orderReza = Order::create([
                'user_id' => $reza->id,
                'type' => 'buy',
                'amount' => 5.000,
                'price' => 10_000_000,
                'remaining_amount' => 5.000,
            ]);

            // 3. Create Sell Order (Akbar)
            $orderAkbar = Order::create([
                'user_id' => $akbar->id,
                'type' => 'sell',
                'amount' => 10.000,
                'price' => 10_000_000,
                'remaining_amount' => 10.000,
            ]);

            // 4. Match Buy Orders with Sell Order (Ahmad, Reza buys from Akbar)
            $this->matchOrder($orderAhmad, $orderAkbar);
            $this->matchOrder($orderReza, $orderAkbar);
        });
    }

    private function matchOrder(Order $buy, Order $sell)
    {
        $matchAmount = min($buy->remaining_amount, $sell->remaining_amount);
        if ($matchAmount <= 0) return;

        $price = $sell->price;
        $total = $matchAmount * $price;

        [$feeBuyer, $feeSeller] = [$this->calculateFee($matchAmount), $this->calculateFee($matchAmount)];

        Trade::create([
            'buy_order_id' => $buy->id,
            'sell_order_id' => $sell->id,
            'buyer_id' => $buy->user_id,
            'seller_id' => $sell->user_id,
            'amount' => $matchAmount,
            'price' => $price,
            'fee_buyer' => $feeBuyer,
            'fee_seller' => $feeSeller,
            'total' => $total,
        ]);

        $buy->remaining_amount -= $matchAmount;
        $buy->status = $buy->remaining_amount > 0 ? 'open' : 'filled';
        $buy->save();

        $sell->remaining_amount -= $matchAmount;
        $sell->status = $sell->remaining_amount > 0 ? 'open' : 'filled';
        $sell->save();
    }

    private function calculateFee(float $gram): int
    {
        $rate = 0;
        if ($gram <= 1.0) {
            $rate = 0.02;
        } elseif ($gram <= 10.0) {
            $rate = 0.015;
        } else {
            $rate = 0.01;
        }

        $fee = $gram * 10_000_000 * $rate;

        return (int) max(50_000, min($fee, 5_000_000));
    }
}
