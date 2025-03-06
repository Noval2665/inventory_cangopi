<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\DescriptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CateringController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketListController;
use App\Http\Controllers\OpnameController;
use App\Http\Controllers\OrderListController;
use App\Http\Controllers\ParStockController;
use App\Http\Controllers\PurchaseReceptionController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockExpenditureController;
use App\Http\Controllers\UserController;
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

    // 👉 Masterdata
    Route::apiResource('inventories', InventoryController::class);
    Route::apiResource('storages', StorageController::class);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('sub-categories', SubCategoryController::class);
    Route::apiResource('descriptions', DescriptionController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('metrics', MetricController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('recipes', RecipeController::class);

    // 👉 Transaction
    Route::apiResource('order-lists', OrderListController::class);
    Route::apiResource('market-lists', MarketListController::class);
    Route::apiResource('purchase-receptions', PurchaseReceptionController::class);

    Route::apiResource('caterings', CateringController::class);
    Route::apiResource('stock-expenditures', StockExpenditureController::class);

    Route::apiResource('purchase-returns', PurchaseReturnController::class);

    Route::apiResource('opnames', OpnameController::class);

    Route::apiResource('par-stocks', ParStockController::class);

    // 👉 Report
    Route::group(['prefix' => 'report'], function () {
        Route::get('order-lists', [ReportController::class, 'orderLists']);
        Route::get('market-lists', [ReportController::class, 'marketLists']);
        Route::get('purchase-receptions', [ReportController::class, 'purchaseReceptions']);

        Route::get('stock-expenditures', [ReportController::class, 'stockExpenditures']);

        Route::group(['prefix' => 'return'], function () {
            Route::get('purchases', [ReportController::class, 'purchaseReturns']);
        });

        Route::get('par-stocks', [ReportController::class, 'parStocks']);
        Route::get('opnames', [ReportController::class, 'opnames']);
    });

    // 👉 Access control
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
});
