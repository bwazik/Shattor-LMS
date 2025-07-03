<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

use App\Http\Controllers\Student\Activities\QuizzesController;
use App\Http\Controllers\Student\Activities\AssignmentsController;

use App\Http\Controllers\Student\Misc\FaqsController;
use App\Http\Controllers\Student\Misc\HelpCenterController;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/student',
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:student']
    ],
    function () {

        Route::name('student.')->group(function () {

            Route::get('/dashboard', function () {
                return view('student.dashboard');
            })->name('dashboard');

            # Account
            Route::prefix('account')->controller(AccountController::class)->name('account.')->group(function () {
                Route::post('/qr/{uuid}', 'scanQRCode')->name('qr.scan');
                Route::get('personal', 'editPersonalInfo')->name('personal.edit');
                Route::post('update-profile-pic', 'updateProfilePic')->name('updateProfilePic')->middleware('throttle:5,1');
                Route::post('personal', 'updatePersonalInfo')->name('personal.update')->middleware('throttle:5,1');
                Route::get('security', 'securityIndex')->name('security.index');
                Route::post('security/password/update', 'updatePassword')->name('password.update')->middleware('throttle:5,1');
                Route::get('coupons', 'getCoupons')->name('coupons.index');
                Route::post('coupons/redeem', 'redeemCoupon')->name('coupons.redeem')->middleware('throttle:5,1');
            });

            # Start Activities
                # Quizzes
                Route::prefix('quizzes')->controller(QuizzesController::class)->name('quizzes.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{uuid}/notices', 'notices')->name('notices');
                    Route::get('/{uuid}/take/{order?}', 'take')->name('take')->middleware('throttle:20,1');
                    Route::get('/{uuid}/review', 'review')->name('review');
                    Route::middleware('throttle:20,1')->group(function () {
                        Route::post('/{uuid}/submit', 'submitAnswer')->name('submit');
                        Route::post('/{uuid}/cheat-detector', 'cheatDetector')->name('cheatDetector');
                        Route::post('/{uuid}/violation', 'violation')->name('violation');
                    });
                });
                # Assignments
                Route::prefix('assignments')->controller(AssignmentsController::class)->name('assignments.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{uuid}', 'details')->name('details');
                    Route::get('assignments/{fileId}/download', 'downloadAssignment')->name('download');
                    Route::get('files/{fileId}/download', 'downloadFile')->name('files.download');
                    Route::get('/{uuid}/review', 'review')->name('review');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('{uuid}/upload', 'uploadFile')->name('files.upload');
                        Route::post('files/delete', 'deleteFile')->name('files.delete');
                    });
                });
            # End Activities

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
