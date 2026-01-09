<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Parent\Misc\FaqsController;
use App\Http\Controllers\Parent\Misc\HelpCenterController;


Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/parent',
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:parent']
    ],
    function () {

        Route::name('parent.')->group(function () {

            Route::get('/dashboard', function () {
                return view('parent.dashboard');
            })->name('dashboard');

            # Account
            Route::prefix('account')->controller(AccountController::class)->name('account.')->group(function () {
                Route::post('/qr/{uuid}', 'scanQRCode')->name('qr.scan');
                Route::get('personal', 'editPersonalInfo')->name('personal.edit');
                Route::post('personal', 'updatePersonalInfo')->name('personal.update')->middleware('throttle:5,1');
                Route::get('security', 'securityIndex')->name('security.index');
                Route::post('security/password/update', 'updatePassword')->name('password.update')->middleware('throttle:5,1');
            });

            # Start Misc
            Route::prefix('faqs')->controller(FaqsController::class)->name('faqs.')->group(function () {
                Route::get('/', 'index')->name('index');
            });
            Route::prefix('help-center')->controller(HelpCenterController::class)->name('help-center.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{categorySlug}/{articleSlug}', 'show')->name('show');
            });
            # End Misc
        });
    }
);
