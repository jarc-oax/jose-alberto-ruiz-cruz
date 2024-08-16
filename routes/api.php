<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\CatalogProductController;

Route::controller(RegisterController::class)->group(
    function() {
        Route::post('register', 'register');
        Route::post('login', 'login');
    }
);
         
Route::middleware('auth:sanctum')->group(
    function() {
        Route::resource('products', CatalogProductController::class);
        Route::post('products/batch-insert', [CatalogProductController::class, 'insertBatch']);
        Route::post('products/batch-show', [CatalogProductController::class, 'showBatch']);
        Route::post('products/batch-update', [CatalogProductController::class, 'updateBatch']);
        Route::post('products/batch-delete', [CatalogProductController::class, 'deleteBatch']);
    }
);