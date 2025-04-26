<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;

class OrderService
{

    /**
     * @param User $user
     * @param float $grams
     * @param int $price
     * @return Order
     * @throws \Exception
     */
    public function createBuyOrder(User $user, float $grams, int $price): Order
    {
        $totalCost = $grams * $price;

        $walletService = new WalletService();
        $rialWallet = $walletService->getWallet($user, 'rial');

        $walletService->withdraw($rialWallet, $totalCost, 'سفارش خرید طلا');

        return Order::create([
            'user_id' => $user->id,
            'type' => 'buy',
            'amount' => $grams,
            'price_per_gram' => $price,
        ]);
    }

    /**
     * @param User $user
     * @param float $grams
     * @param int $price
     * @return Order
     * @throws \Exception
     */
    public function createSellOrder(User $user, float $grams, int $price): Order
    {
        $walletService = new WalletService();
        $goldWallet = $walletService->getWallet($user, 'gold');

        $walletService->withdraw($goldWallet, $grams, 'سفارش فروش طلا');

        return Order::create([
            'user_id' => $user->id,
            'type' => 'sell',
            'amount' => $grams,
            'price_per_gram' => $price,
        ]);
    }

    /**
     * @param Order $newOrder
     * @return void
     */
    public function match(Order $newOrder): void
    {
        if ($newOrder->isBuy()) {
            $oppositeOrders = Order::where('type', 'sell')
                ->where('price_per_gram', '<=', $newOrder->price_per_gram)
                ->whereIn('status', ['open', 'partial'])
                ->orderBy('price_per_gram')
                ->orderBy('created_at')
                ->get();
        } else {
            $oppositeOrders = Order::where('type', 'buy')
                ->where('price_per_gram', '>=', $newOrder->price_per_gram)
                ->whereIn('status', ['open', 'partial'])
                ->orderByDesc('price_per_gram')
                ->orderBy('created_at')
                ->get();
        }

        foreach ($oppositeOrders as $opposite) {
            if ($newOrder->remaining_amount <= 0) break;

            $tradeAmount = min($newOrder->remaining_amount, $opposite->remaining_amount);
            $price = $opposite->price_per_gram;

            [$buyerFee, $sellerFee] = $this->calculateFees($tradeAmount, $price);

            $newOrder->remaining_amount -= $tradeAmount;
            $opposite->remaining_amount -= $tradeAmount;

            $this->updateOrderStatus($newOrder);
            $this->updateOrderStatus($opposite);

            $newOrder->save();
            $opposite->save();

            // Save Trade
            Trade::create([
                'buy_order_id' => $newOrder->isBuy() ? $newOrder->id : $opposite->id,
                'sell_order_id' => $newOrder->isSell() ? $newOrder->id : $opposite->id,
                'amount' => $tradeAmount,
                'price_per_gram' => $price,
                'fee_buyer' => $buyerFee,
                'fee_seller' => $sellerFee,
            ]);

            $this->processBalances($newOrder, $opposite, $tradeAmount, $price, $buyerFee, $sellerFee);
        }
    }

    /**
     * @param float $grams
     * @param int $price
     * @return int[]
     */
    private function calculateFees(float $grams, int $price): array
    {
        $total = $grams * $price;
        $percent = match (true) {
            $grams <= 1   => 0.02,
            $grams <= 10  => 0.015,
            default       => 0.01,
        };

        $fee = $total * $percent;

        $fee = max(50_000, $fee);
        $fee = min(5_000_000, $fee);

        return [(int) $fee, (int) $fee]; // both buyer & seller pay equal fee
    }

    /**
     * @param Order $order
     * @return void
     */
    private function updateOrderStatus(Order $order): void
    {
        if ($order->remaining_amount == 0) {
            $order->status = 'filled';
        } elseif ($order->remaining_amount < $order->amount) {
            $order->status = 'partial';
        }
    }

    /**
     * @param Order $order1
     * @param Order $order2
     * @param float $grams
     * @param int $price
     * @param int $buyerFee
     * @param int $sellerFee
     * @return void
     */
    private function processBalances(
        Order $order1, Order $order2,
        float $grams, int $price,
        int $buyerFee, int $sellerFee
    ): void {
        $walletService = new WalletService();

        $buyer  = $order1->isBuy() ? $order1->user : $order2->user;
        $seller = $order1->isSell() ? $order1->user : $order2->user;

        $rialAmount = $grams * $price;

        $walletService->deposit($walletService->getWallet($seller, 'rial'), $rialAmount - $sellerFee, 'فروش طلا');
        $walletService->deposit($walletService->getWallet($buyer, 'gold'), $grams, 'خرید طلا');

    }

}
