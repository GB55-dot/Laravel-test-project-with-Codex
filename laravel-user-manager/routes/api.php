<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'user.auth', 'throttle:60,1'])
    ->name('api.')
    ->group(function (): void {
        Route::apiResource('users', UserController::class);
    });
