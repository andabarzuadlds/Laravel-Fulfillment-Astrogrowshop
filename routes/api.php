<?php

use App\Http\Controllers\SetupOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ProductCostsController;
use App\Http\Controllers\SetupOrderBonglabController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function(){

    Route::get('/setup/order', [SetupOrderController::class, 'setupFulfillmentOrder']);
    Route::get('/setup/getBsaleDocuments', [SetupOrderController::class, 'getBsaleDocuments']);
    Route::get('/setup_bonglab/order', [SetupOrderBonglabController::class, 'setupFulfillmentOrder']);
    Route::get('/setup/order_v2', [SetupOrderController::class, 'setupFulfillmentOrderV2']);
    Route::get('/setup/order_by_id/', [SetupOrderController::class, 'setupFulfillmentOrderById']);
    Route::get('/setup/validateStock/{sku}', [SetupOrderController::class, 'validate_stock_defontana']);
    
    Route::get('/stock/sync_stock_defontana', function(){
        
       $stock =  new StockController;
       $stock->sync_stock_task();
       $stock->check_products_tienda();
       
    });
     
    
    Route::get('/stock/sync_stock/', [StockController::class, 'sync_stock']);
    Route::get('/stock/sync_stock_task/', [StockController::class, 'sync_stock_task']);
    Route::get('/stock/sync_stock_test/', [StockController::class, 'sync_stock_test']);
    Route::get('/stock/sync_stock_delight/', [StockController::class, 'sync_stock_delight']);
    Route::get('/stock/check_stock_tienda/', [StockController::class, 'check_products_tienda']);
    Route::get('/stock/sync_stock_by_sku/{sku}', [StockController::class, 'sync_stock_by_sku']);
    Route::get('/stock/getBsaleProductID/{sku}', [StockController::class, 'getBsaleProductID']);
    Route::get('/stock/getDLDSProductPrice/{sku}', [StockController::class, 'getDLDSProductPrice']);
    Route::get('/stock/get_bsale_stock/{sku}', [StockController::class, 'get_bsale_stock']);
    Route::post('/stock/notification/', [StockController::class, 'get_stock_defontana_notification']);
    
    Route::get('/costs/sync_costs/', [StockController::class, 'sync_costs']);
    Route::get('/costs/get_costs/', [ProductCostsController::class, 'get_product_prices']);
    Route::get('/product/add_product/{sku}', [ProductCostsController::class, 'add_product']);
        Route::get('/product/add_product_delight/{sku}', [ProductCostsController::class, 'add_product']);
    Route::get('/product/add_product_massive/', [ProductCostsController::class, 'masive_add_products']);
    Route::get('/costs/sync_costs_by_sku/{sku}', [ProductCostsController::class, 'sync_costs_by_sku']);
    Route::get('/costs/add_products_to_sync_cole/', [ProductCostsController::class, 'add_products_to_sync_cole']);
    Route::get('/costs/test/{sku}', [ProductCostsController::class, 'test']);
});