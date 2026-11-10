<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
