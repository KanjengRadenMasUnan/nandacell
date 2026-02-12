<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong', 'status' => 'ok']);
});
Route::post('/products', [ApiController::class, 'addProduct']);   
Route::get('/products/search', [ApiController::class, 'searchProduct']);   
Route::get('/products/{code}', [ApiController::class, 'getProduct']); 
Route::put('/products/{id}', [ApiController::class, 'updateProduct']);
Route::delete('/products/{id}', [ApiController::class, 'deleteProduct']);
Route::get('/products', [ApiController::class, 'getAllProducts']);
Route::post('/checkout', [ApiController::class, 'checkout']);        
Route::get('/transactions', [ApiController::class, 'history']);
