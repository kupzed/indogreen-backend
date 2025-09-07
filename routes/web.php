<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'Message' => 'API OK',
        'Laravel' => app()->version(),
    ]);
});
