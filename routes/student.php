<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

use App\Http\Controllers\Api\DataFetchController;


use App\Http\Controllers\Student\DashboardController;

use App\Http\Controllers\AccountController;

use App\Http\Controllers\Student\Misc\FaqsController;
use App\Http\Controllers\Student\Misc\HelpCenterController;

use App\Http\Controllers\Student\Tools\ResourcesController;

use App\Http\Controllers\Student\Activities\ZoomsController;
use App\Http\Controllers\Student\Activities\QuizzesController;
use App\Http\Controllers\Student\Activities\OfflineQuizzesController;
use App\Http\Controllers\Student\Activities\AssignmentsController;
use App\Http\Controllers\Student\Activities\CompensatoriesController;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/student',
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:student']
    ],
    function () {

        Route::name('student.')->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            # Api Responses
            Route::prefix('fetch')->controller(DataFetchController::class)->name('fetch.')->group(function () {
                Route::get('teachers/{teacher}/groups', 'getTeacherGroups')->name('teachers.groups');
                Route::get('groups/{group}/lessons', 'getGroupLessonsForCompensatory')->name('groups.lessons');
            });

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

            # Start Tools
                # Resources
                Route::prefix('resources')->controller(ResourcesController::class)->name('resources.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{uuid}', 'details')->name('details');
                    Route::get('{uuid}/download', 'downloadFile')->name('download');
                    Route::middleware('throttle:20,1')->group(function () {
                        Route::post('/{uuid}/track', 'trackEvent')->name('track');
                        Route::post('/{uuid}/cheat-detector', 'cheatDetector')->name('cheatDetector');
                    });
                });
            # End Tools

            # Start Activities
                # Compensatories
                Route::prefix('compensatories')->controller(CompensatoriesController::class)->name('compensatories.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                    });
                });

                # Zooms
                Route::prefix('zooms')->controller(ZoomsController::class)->name('zooms.')->group(function () {
                    Route::get('/', 'index')->name('index');
                });

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

                # Offline Quizzes
                Route::prefix('offline-quizzes')->controller(OfflineQuizzesController::class)->name('offline-quizzes.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{uuid}/review', 'review')->name('review');
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
