<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\InventoryController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// 👉 Api here

// 👉 Anyone can access these routes

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('register', [AuthController::class, 'register']);
});

// Only authenticated user can access this route
Route::group(['middleware' => 'authenticated'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::get('me', [AuthController::class, 'me']);
    });


    // 👉 Master-data
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('sub-categories', SubCategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('metrics', MetricController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('storages', StorageController::class);
    Route::apiResource('inventories', InventoryController::class);
});
