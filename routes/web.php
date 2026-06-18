<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/reset-password', function (Request $request) {
    return view('reset-password', [
        'token' => $request->token,
        'email' => $request->email,
    ]);
})->name('password.reset');
