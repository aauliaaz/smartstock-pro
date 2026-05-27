<?php

use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [AuthController::class, 'login'])
    ->withoutMiddleware([ValidateCsrfToken::class]);
Route::post('/logout', [AuthController::class, 'logout'])
    ->withoutMiddleware([ValidateCsrfToken::class]);

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
