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
use App\Http\Controllers\RoleController;
use App\Http\Controllers\FinanceController;

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:api')->name('profile.update');
    Route::put('/password', [AuthController::class, 'changePassword'])->middleware('auth:api')->name('password.update');

    // ===== ROLE ENDPOINTS =====

    // Role & permission user yang sedang login (boleh diakses semua user yang sudah login)
    Route::get('/role/me', [RoleController::class, 'me'])
        ->middleware('auth:api')
        ->name('role.me');

    // Daftar user untuk dikelola (super_admin & admin)
    Route::get('/role/users', [RoleController::class, 'users'])
        ->middleware(['auth:api', 'role:super_admin|admin'])
        ->name('role.users');

    // Update role & job user lain (super_admin & admin)
    Route::put('/role', [RoleController::class, 'update'])
        ->middleware(['auth:api', 'role:super_admin|admin'])
        ->name('role.update');

    // Config role & permission (modul & label)
    Route::get('/role/config', [RoleController::class, 'config'])
        ->middleware('auth:api')
        ->name('role.config');
});

Route::group(['middleware' => 'auth:api'], function () {

    // Project
    Route::get('projects/getFormDependencies', [ProjectController::class, 'getFormDependencies']);
    Route::get('projects/customers', [ProjectController::class, 'getCustomersForProject']);
    Route::patch('projects/{project}/toggle-cert', [ProjectController::class, 'toggleCertProject']);
    Route::get('projects/certificate/list', [ProjectController::class, 'getCertProjects']);

    // Resource projects
    Route::apiResource('projects', ProjectController::class);

    // Mitra/Partner
    Route::apiResource('mitras', MitraController::class);
    Route::get('mitra/customers', [MitraController::class, 'getCustomers']);
    Route::get('mitra/vendors', [MitraController::class, 'getVendors']);

    // Activity
    Route::apiResource('activities', ActivityController::class);
    Route::get('activity/getFormDependencies', [ActivityController::class, 'getFormDependencies']);

    // Barang Certificate
    Route::apiResource('barang-certificates', BarangCertificateController::class);
    Route::get('barang-certificate/getFormDependencies', [BarangCertificateController::class, 'getFormDependencies']);

    // Certificate
    Route::apiResource('certificates', CertificateController::class);
    Route::get('certificate/getFormDependencies', [CertificateController::class, 'getFormDependencies']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Finance Report
    Route::get('finance/monthly-report', [FinanceController::class, 'monthlyReport']);
    Route::patch('finance/{activity}/value', [FinanceController::class, 'updateValue']);

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/recent', [ActivityLogController::class, 'getRecent']);
    Route::get('activity-logs/stats', [ActivityLogController::class, 'getStats']);
    Route::get('activity-logs/filter-options', [ActivityLogController::class, 'getFilterOptions']);
    Route::get('activity-logs/{modelType}/{modelId}', [ActivityLogController::class, 'getModelLogs']);
    Route::get('activity-logs/export', [ActivityLogController::class, 'export']);
    Route::delete('activity-logs', [ActivityLogController::class, 'deleteUserLogs']);
});
