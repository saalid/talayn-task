<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;

class WalletService
{
    /**
     * @param User $user
     * @param string $type
     * @return Wallet
     */
    public function getWallet(User $user, string $type): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['balance' => 0]
        );
    }

    /**
     * @param Wallet $wallet
     * @param float $amount
     * @param string|null $description
     * @param $sourceType
     * @param $sourceId
     * @return WalletTransaction
     */
    public function deposit(Wallet $wallet, float $amount, string $description = null, $sourceType = null, $sourceId = null): WalletTransaction
    {
        $wallet->balance += $amount;
        $wallet->save();

        return $wallet->transactions()->create([
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    /**
     * @param Wallet $wallet
     * @param float $amount
     * @param string|null $description
     * @param $sourceType
     * @param $sourceId
     * @return WalletTransaction
     * @throws \Exception
     */
    public function withdraw(Wallet $wallet, float $amount, string $description = null, $sourceType = null, $sourceId = null): WalletTransaction
    {
        if ($wallet->balance < $amount) {
            throw new \Exception("Insufficient balance.");
        }

        $wallet->balance -= $amount;
        $wallet->save();

        return $wallet->transactions()->create([
            'amount' => -$amount,
            'balance_after' => $wallet->balance,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }
}
