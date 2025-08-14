<?php
 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BarangCertificateController;
use App\Http\Controllers\CertificateController;
 
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
    Route::get('/activity/getFormDependencies', [ActivityController::class, 'getFormDependencies']);

    // Barang Certificate
    Route::apiResource('barang-certificates', BarangCertificateController::class);
    Route::get('/barang-certificate/getFormDependencies', [BarangCertificateController::class, 'getFormDependencies']);

    // Certificate
    Route::apiResource('certificates', CertificateController::class);
    Route::get('/certificate/getFormDependencies', [CertificateController::class, 'getFormDependencies']);
    Route::get('/certificate/getBarangCertificatesByProject/{projectId}', [CertificateController::class, 'getBarangCertificatesByProject']);

    // Dashboard (misal hanya index)
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/recent', [ActivityLogController::class, 'getRecent']);
    Route::get('activity-logs/stats', [ActivityLogController::class, 'getStats']);
    Route::get('activity-logs/filter-options', [ActivityLogController::class, 'getFilterOptions']);
    Route::get('activity-logs/{modelType}/{modelId}', [ActivityLogController::class, 'getModelLogs']);
    Route::get('activity-logs/export', [ActivityLogController::class, 'export']);
    Route::delete('activity-logs', [ActivityLogController::class, 'deleteUserLogs']);
});