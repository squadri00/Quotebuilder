<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegistrationOtpController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    // Registration has no Stripe card check on the Free plan to slow
    // spammers down the way paid plans do, so it gets its own throttle
    // rather than relying on anything else in the request pipeline.
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

    // Free-plan email verification step. throttle:6,1 on 'verify' limits
    // guessing the 6-digit code; the code itself also expires after 10
    // minutes (see PendingRegistration::issueOtp), so the two together
    // make brute-forcing it impractical. 'resend' has its own, tighter
    // throttle so someone can't use it to spam a stranger's inbox.
    Route::get('register/verify/{token}', [RegistrationOtpController::class, 'show'])
        ->name('register.verify');

    Route::post('register/verify/{token}', [RegistrationOtpController::class, 'verify'])
        ->middleware('throttle:6,1');

    Route::post('register/verify/{token}/resend', [RegistrationOtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('register.verify.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
