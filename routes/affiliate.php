<?php

use App\Http\Controllers\Affiliate\Auth\LoginController;
use App\Http\Controllers\Affiliate\Auth\RegisterController;
use App\Http\Controllers\Affiliate\CommissionController;
use App\Http\Controllers\Affiliate\DashboardController;
use App\Http\Controllers\Affiliate\EstimatorController;
use App\Http\Controllers\Affiliate\ProfileController;
use App\Http\Controllers\Affiliate\ProspectController;
use App\Http\Controllers\Affiliate\ReferralListController;
use App\Http\Controllers\Affiliate\StatementController;
use Illuminate\Support\Facades\Route;

/**
 * Partner portal. Its own `affiliate` guard — a business user or a super
 * admin signed in elsewhere in the same browser is still a guest here.
 */
Route::prefix('affiliate')->name('affiliate.portal.')->group(function () {
    Route::middleware('guest:affiliate')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
        Route::get('/register', [RegisterController::class, 'create'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    });

    Route::middleware(['auth:affiliate', 'affiliate.active'])->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/pending', [DashboardController::class, 'pending'])->name('pending');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/referrals', [ReferralListController::class, 'index'])->name('referrals');

        Route::get('/prospects', [ProspectController::class, 'index'])->name('prospects');
        Route::post('/prospects', [ProspectController::class, 'store'])->name('prospects.store');
        Route::patch('/prospects/{prospect}', [ProspectController::class, 'update'])->name('prospects.update');
        Route::post('/prospects/{prospect}/release', [ProspectController::class, 'release'])->name('prospects.release');
        Route::post('/prospects/{prospect}/renew', [ProspectController::class, 'renew'])->name('prospects.renew');

        Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions');

        Route::get('/statements', [StatementController::class, 'index'])->name('statements');
        Route::post('/statements', [StatementController::class, 'finalize'])->name('statements.finalize');
        Route::post('/statements/{payout}/submit', [StatementController::class, 'submit'])->name('statements.submit');
        Route::get('/statements/{payout}', [StatementController::class, 'show'])->name('statements.show');
        Route::get('/statements/{payout}/txt', [StatementController::class, 'txt'])->name('statements.txt');

        Route::get('/estimator', [EstimatorController::class, 'index'])->name('estimator');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    });
});
