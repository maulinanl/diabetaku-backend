<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminWebController;

Route::get('/reset-password', function (Request $request) {
    return view('reset-password', [
        'token' => $request->token,
        'email' => $request->email,
    ]);
})->name('password.reset');

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/admin', function () {
    return redirect()->route('admin.web.dashboard');
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'loginPage'])
        ->name('admin.login');

    Route::post('/login', [AdminAuthController::class, 'login'])
        ->name('admin.login.process');

    Route::post('/logout', [AdminAuthController::class, 'logout'])
        ->name('admin.logout');

    Route::middleware('admin')->name('admin.web.')->group(function () {
        Route::get('/dashboard', [AdminWebController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/doctors/pending', [AdminWebController::class, 'pendingDoctors'])
            ->name('doctors.pending');

        Route::post('/doctors/{doctorId}/verify', [AdminWebController::class, 'verifyDoctor'])
            ->name('doctors.verify');

        Route::post('/doctors/{doctorId}/reject', [AdminWebController::class, 'rejectDoctor'])
            ->name('doctors.reject');

        Route::get('/users', [AdminWebController::class, 'users'])
            ->name('users.index');

        Route::post('/users/{userId}/status', [AdminWebController::class, 'updateUserStatus'])
            ->name('users.status');

        Route::get('/master/{type?}', [AdminWebController::class, 'masterData'])
            ->name('master.index');

        Route::post('/master/{type}', [AdminWebController::class, 'storeMasterData'])
            ->name('master.store');

        Route::post('/master/{type}/{id}', [AdminWebController::class, 'updateMasterData'])
            ->name('master.update');

        Route::post('/master/{type}/{id}/delete', [AdminWebController::class, 'deleteMasterData'])
            ->name('master.delete');

        Route::post('/users/{userId}/reset-password', [AdminWebController::class, 'resetUserPassword'])
            ->name('users.reset-password');

        Route::post('/users/{userId}/send-reset-link', [AdminWebController::class, 'sendUserResetPasswordLink'])
            ->name('users.send-reset-link');
    });
});
