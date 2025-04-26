<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

/**
 * @OA\Info(title="Gold Market API", version="1.0.0")
 *
 * @OA\Server(url="http://localhost:8000/api")
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders/buy",
     *     summary="Place a buy order",
     *     description="Place a buy order for gold",
     *     operationId="buyOrder",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "price_per_gram"},
     *             @OA\Property(property="amount", type="number", example=2, description="Amount of gold to buy"),
     *             @OA\Property(property="price_per_gram", type="integer", example=10000000, description="Price per gram in Toman")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Buy order placed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Buy order placed"),
     *             @OA\Property(property="order", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */

    /**
     * @param Request $request
     * @param OrderService $orderService
     * @return \Illuminate\Http\JsonResponse
     */
    public function buy(Request $request, OrderService $orderService)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.001',
            'price_per_gram' => 'required|integer|min:1000',
        ]);

        $order = Order::create([
            'user_id' => auth()->id(),
            'type' => 'buy',
            'amount' => $data['amount'],
            'remaining_amount' => $data['amount'],
            'price_per_gram' => $data['price_per_gram'],
            'status' => 'open',
        ]);

        $orderService->match($order);

        return response()->json(['message' => 'Buy order placed', 'order' => $order]);
    }

    /**
     * @param Request $request
     * @param OrderService $orderService
     * @return \Illuminate\Http\JsonResponse
     */
    public function sell(Request $request, OrderService $orderService)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.001',
            'price_per_gram' => 'required|integer|min:1000',
        ]);

        $order = Order::create([
            'user_id' => auth()->id(),
            'type' => 'sell',
            'amount' => $data['amount'],
            'remaining_amount' => $data['amount'],
            'price_per_gram' => $data['price_per_gram'],
            'status' => 'open',
        ]);

        $orderService->match($order);

        return response()->json(['message' => 'Sell order placed', 'order' => $order]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $orders = auth()->user()->orders()->latest()->get();
        return response()->json($orders);
    }

    /**
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if (in_array($order->status, ['filled', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel'], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Order cancelled']);
    }
}
