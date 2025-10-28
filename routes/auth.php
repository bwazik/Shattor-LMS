<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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


        Route::controller(RegisteredUserController::class)->group(function () {
            Route::get('register', 'create')->name('register');
            Route::post('register', 'store');
        })->middleware('throttle:5');
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
