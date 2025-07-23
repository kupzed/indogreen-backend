<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DashboardController;
 
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
});

Route::group(['middleware' => 'auth:api'], function () {
    // Project
    Route::apiResource('projects', ProjectController::class);

    // Mitra/Partner
    Route::apiResource('mitras', MitraController::class);
    Route::get('/mitra/customers', [MitraController::class, 'getCustomers']);
    Route::get('/mitra/vendors', [MitraController::class, 'getVendors']);

    // Activity
    Route::apiResource('activities', ActivityController::class);

    // Dashboard (misal hanya index)
    Route::get('dashboard', [DashboardController::class, 'index']);
});