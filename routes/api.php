<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/products', [ProductController::class,'index']);
    Route::post('/products', [ProductController::class,'store']);
    Route::delete('/products/{product}', [ProductController::class,'destroy']);
    Route::get('/products/{product}', [ProductController::class,'show']);

    Route::post('/sales', [SaleController::class,'store']);
    Route::get('/sales', [SaleController::class,'index']);

    Route::get('/reports/employee/{employeeId}', [ProductController::class,'reportForEmployee']);
});

// Authentication routes (token based)
Route::post('/login', [AuthController::class,'login']);
Route::post('/register', [AuthController::class,'register']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class,'logout']);
Route::middleware('auth:sanctum')->get('/users', [AuthController::class,'users']);
Route::middleware('auth:sanctum')->post('/users/{id}/role', [AuthController::class,'assignRole']);
