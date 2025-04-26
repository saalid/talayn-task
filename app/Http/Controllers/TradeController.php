<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/api/trades",
 *     summary="Get user trades",
 *     description="Get the list of trades related to the authenticated user",
 *     operationId="getUserTrades",
 *     tags={"Trades"},
 *     @OA\Response(
 *         response=200,
 *         description="List of user trades",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(type="object")
 *         )
 *     )
 * )
 */
class TradeController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $userId = auth()->id();

        $trades = Trade::with(['buyOrder', 'sellOrder'])
            ->whereHas('buyOrder', fn($q) => $q->where('user_id', $userId))
            ->orWhereHas('sellOrder', fn($q) => $q->where('user_id', $userId))
            ->latest()
            ->get();

        return response()->json($trades);
    }
}
