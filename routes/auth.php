<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'guest:web,teacher,assistant,student,parent']
    ],
    function () {
        Route::controller(AuthenticatedSessionController::class)->group(function () {
            Route::get('{guard}/login', 'create')->name('login');
            Route::post('{guard}/login', 'store');
        })->middleware('throttle:login');

        Route::get('/login', function () {
            return view('auth.choose');
        })->name('login.choose');

        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('/register', function () {
            return view('auth.register');
        })->name('register');

    }
);

Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::post('{guard}/logout', 'destroy')
        ->middleware(['auth:web,teacher,assistant,student,parent'])
        ->where('guard', 'web|teacher|assistant|student|parent')
        ->name('logout');
});

// Route::middleware('auth')->group(function () {
//     // Route::get('verify-email', EmailVerificationPromptController::class)
//     //     ->name('verification.notice');

//     // Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
//     //     ->middleware(['signed', 'throttle:6,1'])
//     //     ->name('verification.verify');

//     // Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
//     //     ->middleware('throttle:6,1')
//     //     ->name('verification.send');

//     Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
//         ->name('password.confirm');

//     Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

// Route::put('password', [PasswordController::class, 'update'])->name('password.update');


// });
