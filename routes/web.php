<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CandidateCvController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('profile', [CandidateProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [CandidateProfileController::class, 'update'])->name('profile.update');

    Route::resource('users', UserController::class);
    Route::get('users/{user}/cv', CandidateCvController::class)->name('users.cv');
});
