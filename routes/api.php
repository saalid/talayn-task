<?php


use App\Http\Controllers\OrderController;
use App\Http\Controllers\TradeController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::post('/login', function (Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $user->createToken('my-token')->plainTextToken,
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('/buy', [OrderController::class, 'buy']);
        Route::post('/sell', [OrderController::class, 'sell']);
        Route::get('/', [OrderController::class, 'index']);
        Route::delete('{order}', [OrderController::class, 'cancel']);
    });

    Route::get('/trades', [TradeController::class, 'index']);
});
