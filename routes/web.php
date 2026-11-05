
<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [
        LoginController::class,
        'showLogin',
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login',
    ])->middleware('throttle:5,1')->name('login.submit');

    Route::get('/register', [
        RegisterController::class,
        'showRegister',
    ])->name('register');

    Route::post('/register', [
        RegisterController::class,
        'register',
    ])->middleware('throttle:5,1')->name('register.submit');
});

Route::post('/logout', [
    LoginController::class,
    'logout',
])->middleware('auth')->name('logout');
