<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global middleware (dijalankan di setiap request).
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Middleware global yang kamu butuhkan
        \Illuminate\Http\Middleware\HandleCors::class,
        // kalau kamu punya middleware global lain, boleh ditambahkan di sini
    ];

    /**
     * Middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // Kalau app kamu API-only, bisa dibiarkan kosong
        ],

        'api' => [
            // Misal mau tambahkan throttle/bindings di sini nanti:
            // \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            // \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Middleware aliases (Laravel 11 pakai ini untuk route).
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        // alias dasar yang dipakai di routes/api.php
        'auth'     => \Illuminate\Auth\Middleware\Authenticate::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // 🔹 Spatie permission middlewares (PERHATIKAN: `Middlewares` pakai S di belakang)
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ];
}
