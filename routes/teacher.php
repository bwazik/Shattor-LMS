<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

use App\Http\Controllers\Api\DataFetchController;

use App\Http\Controllers\Teacher\Platform\PlansController;

use App\Http\Controllers\Teacher\Tools\GradesController;
use App\Http\Controllers\Teacher\Tools\GroupsController;
use App\Http\Controllers\Teacher\Tools\LessonsController;
use App\Http\Controllers\Teacher\Tools\ResourcesController;

use App\Http\Controllers\Teacher\Users\AssistantsController;
use App\Http\Controllers\Teacher\Users\StudentsController;
use App\Http\Controllers\Teacher\Users\StudentsProfileController;
use App\Http\Controllers\Teacher\Users\ParentsController;

use App\Http\Controllers\Teacher\Activities\CompensatoriesController;
use App\Http\Controllers\Teacher\Activities\AttendanceController;
use App\Http\Controllers\Teacher\Activities\ZoomsController;
use App\Http\Controllers\Teacher\Activities\QuizzesController;
use App\Http\Controllers\Teacher\Activities\QuestionsController;
use App\Http\Controllers\Teacher\Activities\AnswersController;
use App\Http\Controllers\Teacher\Activities\OfflineQuizzesController;
use App\Http\Controllers\Teacher\Activities\AssignmentsController;

use App\Http\Controllers\Teacher\Finance\VerifyFinancalPinController;
use App\Http\Controllers\Teacher\Finance\FeesController;
use App\Http\Controllers\Teacher\Finance\StudentFeesController;
use App\Http\Controllers\Teacher\Finance\InvoicesController;
use App\Http\Controllers\Teacher\Finance\TransactionsController;

use App\Http\Controllers\Teacher\Account\BillingController;
use App\Http\Controllers\Teacher\Account\SubscriptionsController;
use App\Http\Controllers\AccountController;

use App\Http\Controllers\Teacher\Misc\FaqsController;
use App\Http\Controllers\Teacher\Misc\HelpCenterController;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/teacher',
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:teacher']
    ],
    function () {

        Route::name('teacher.')->group(function () {

            # Start Unsubscriped Routes
            # Plans
            Route::prefix('plans')->controller(PlansController::class)->name('plans.')->group(function () {
                Route::get('/', 'index')->name('index');
            });

            # Billing
            Route::prefix('billing')->controller(BillingController::class)->name('billing.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/transactions', 'transactions')->name('transactions');
                Route::prefix('invoices')->group(function () {
                    Route::get('/', 'invoices')->name('invoices');
                    Route::get('{uuid}/print', 'printInvoice')->name('invoices.print');
                    Route::get('{uuid}', 'previewInvoice')->name('invoices.preview');
                    Route::middleware(['signed', 'throttle:5,1'])->group(function () {
                        Route::get('{uuid}/pay', 'payInvoice')->name('invoices.pay');
                        Route::post('{uuid}/process', 'processPayment')->name('invoices.process');
                    });
                });
            });

            # Subscriptions
            Route::prefix('subscriptions')->controller(SubscriptionsController::class)->name('subscriptions.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::middleware('throttle:5,1')->group(function () {
                    Route::post('insert', 'insert')->name('insert');
                    Route::post('cancle', 'cancle')->name('cancle');
                });
            });

            # Account
            Route::prefix('account')->controller(AccountController::class)->name('account.')->group(function () {
                Route::post('/qr/{uuid}', 'scanQRCode')->name('qr.scan');
                Route::get('personal', 'editPersonalInfo')->name('personal.edit');
                Route::post('update-profile-pic', 'updateProfilePic')->name('updateProfilePic')->middleware('throttle:5,1');
                Route::post('personal', 'updatePersonalInfo')->name('personal.update')->middleware('throttle:5,1');
                Route::get('security', 'securityIndex')->name('security.index');
                Route::post('security/password/update', 'updatePassword')->name('password.update')->middleware('throttle:5,1');
                Route::post('security/security-code/update', 'updateSecurityCode')->name('security-code.update')->middleware('throttle:5,1');
                Route::post('zoom-account/update', 'updateZoomAccount')->name('zoom.update')->middleware('throttle:5,1');
                Route::get('coupons', 'getCoupons')->name('coupons.index');
                Route::post('coupons/redeem', 'redeemCoupon')->name('coupons.redeem')->middleware('throttle:5,1');
                Route::get('fees-pricing/', 'feesPricingIndex')->name('fees.index');
                Route::post('fees-pricing/update', 'updateFeesPricing')->name('fees.update')->middleware('throttle:5,1');
            });

            Route::get('plans/{plan}/{period?}', [DataFetchController::class, 'getPlanData'])->name('fetch.plans.data');


            # Start Misc
            Route::prefix('faqs')->controller(FaqsController::class)->name('faqs.')->group(function () {
                Route::get('/', 'index')->name('index');
            });
            Route::prefix('help-center')->controller(HelpCenterController::class)->name('help-center.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{categorySlug}/{articleSlug}', 'show')->name('show');
            });
            # End Misc
            # End Unsubscriped Routes

            Route::middleware('subscribed')->group(function () {
                Route::get('/dashboard', function () {
                    return view('teacher.dashboard'); })->name('dashboard');

                # Api Responses
                Route::prefix('fetch')->controller(DataFetchController::class)->name('fetch.')->group(function () {
                    Route::get('grades/{grade}/groups', 'getTeacherGroupsByGrade')->name('grade.groups');
                    Route::get('students/{student}', 'getStudentData')->name('students.data');
                    Route::get('students/{student}/fees', 'getStudentFeesByStudent')->name('students.fees');
                    Route::get('students/{student}/student-fees', 'getStudentRegisteredFeesByStudent')->name('students.student-fees');
                    Route::get('fees/{fee}', 'getFeeData')->name('fees.data');
                    Route::get('student-fees/{studentFee}', 'getStudentFeeData')->name('student-fees.data');
                    Route::get('groups/{group}/lessons', 'getGroupLessons')->name('groups.lessons');
                    Route::get('lessons/{lesson}', 'getLessonData')->name('lessons.data');
                    Route::get('students/{student}/groups', 'getStudentGroups')->name('students.groups');
                });

                # Start Tools
                # Grades
                Route::prefix('grades')->controller(GradesController::class)->name('grades.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{gradeId}/groups', 'getTeacherGroupsByGrade')->name('groups');
                });

                # Groups
                Route::prefix('groups')->controller(GroupsController::class)->name('groups.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{uuid}/lessons', 'lessons')->name('lessons');
                    Route::get('{uuid}/students', 'students')->name('students');
                    Route::get('{uuid}/export-qr-codes', 'exportQrCodes')->name('exportQrCodes');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                        Route::post('generate-lessons', 'generateLessons')->name('generateLessons');
                        Route::post('import-students', 'importStudents')->name('importStudents');
                    });
                });

                # Lessons
                Route::prefix('lessons')->controller(LessonsController::class)->name('lessons.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{uuid}/compensatories', 'compensatories')->name('compensatories');
                    Route::get('{uuid}/attendances', 'attendances')->name('attendances');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                    });
                    Route::get('{uuid}/reports', 'reports')->name('reports');
                    Route::get('{uuid}/absent-students', 'absentStudents')->name('absent');
                    Route::get('{uuid}/compensated-students', 'compensatedStudents')->name('compensated');
                    Route::get('{uuid}/present-late-students', 'presentLateStudents')->name('present_late');
                    Route::get('{uuid}/compensatory-students', 'compensatoryStudents')->name('compensatory');
                    Route::get('{uuid}/unrecorded-students', 'unrecordedStudents')->name('unrecorded');
                });

                # Resources
                Route::prefix('resources')->controller(ResourcesController::class)->name('resources.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{uuid}', 'details')->name('details');
                    Route::post('{uuid}/upload', 'uploadFile')->name('upload');
                    Route::get('{uuid}/download', 'downloadFile')->name('download');
                    Route::post('files/delete', 'deleteFile')->name('files.delete');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                    });
                });
                # End Tools

                # Start Users Managment
                # Assistants
                Route::prefix('assistants')->controller(AssistantsController::class)->name('assistants.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                    });
                });

                # Students
                Route::prefix('students')->name('students.')->group(function () {
                    Route::controller(StudentsController::class)->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::middleware('throttle:10,1')->group(function () {
                            Route::post('insert', 'insert')->name('insert');
                            Route::post('update', 'update')->name('update');
                            Route::post('delete', 'delete')->name('delete');
                            Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                        });
                    });
                    Route::controller(StudentsProfileController::class)->group(function () {
                        Route::prefix('{uuid}')->name('profile.')->group(function () {
                            Route::get('profile', 'profile')->name('index');
                            Route::post('update-profile-pic', 'updateProfilePic')->name('updateProfilePic')->middleware('throttle:5,1');
                            Route::get('attendance', 'attendance')->name('attendance');
                            Route::get('quizzes', 'quizzes')->name('quizzes');
                            Route::get('assignments', 'assignments')->name('assignments');
                            Route::get('fees', 'fees')->name('fees');
                            Route::get('security', 'security')->name('security');
                        });
                    });
                });
                # Parents
                Route::prefix('parents')->name('parents.')->group(function () {
                    Route::controller(ParentsController::class)->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::middleware('throttle:10,1')->group(function () {
                            Route::post('insert', 'insert')->name('insert');
                            Route::post('update', 'update')->name('update');
                            Route::post('delete', 'delete')->name('delete');
                            Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                        });
                    });
                    Route::controller(ParentsProfileController::class)->group(function () {
                        Route::prefix('{uuid}')->name('profile.')->group(function () {
                            Route::get('profile', 'profile')->name('index');
                            Route::post('update-profile-pic', 'updateProfilePic')->name('updateProfilePic')->middleware('throttle:5,1');
                        });
                    });
                });
                # End Users Managment

                # Start Activities
                # Compensatories
                Route::prefix('compensatories')->controller(CompensatoriesController::class)->name('compensatories.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('accept', 'accept')->name('accept');
                        Route::post('reject', 'reject')->name('reject');
                        Route::post('accept-selected', 'acceptSelected')->name('acceptSelected');
                        Route::post('reject-selected', 'rejectSelected')->name('rejectSelected');
                    });
                });

                # Attendance
                Route::prefix('attendance')->controller(AttendanceController::class)->name('attendance.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('students', 'getStudentsByFilter')->name('students');
                    Route::post('scan', 'scanAttendance')->name('scan');
                    Route::get('{lessonUuid}/export', 'exportAttendance')->name('export');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                    });
                });

                # Zooms
                Route::prefix('zooms')->controller(ZoomsController::class)->name('zooms.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                    });
                });

                # Quizzes
                Route::prefix('quizzes')->controller(QuizzesController::class)->name('quizzes.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                        Route::post('{uuid}/students/{studentUuid}/reset', 'resetStudentQuiz')->name('resetStudentQuiz');
                    });
                    Route::get('{uuid}/reports', 'reports')->name('reports');
                    Route::get('{uuid}/students/{studentUuid}/review', 'review')->name('review');
                    Route::get('{uuid}/students-taken-quiz', 'studentsTakenQuiz')->name('studentsTakenQuiz');
                    Route::get('{uuid}/students-not-taken-quiz', 'studentsNotTakenQuiz')->name('studentsNotTakenQuiz');
                });

                # Questions
                Route::prefix('quizzes/{quizId}/questions')->controller(QuestionsController::class)->name('questions.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                    });
                });
                Route::prefix('questions')->controller(QuestionsController::class)->name('questions.')->middleware('throttle:10,1')->group(function () {
                    Route::post('update', 'update')->name('update');
                    Route::post('delete', 'delete')->name('delete');
                    Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                });

                # Answers
                Route::prefix('questions/{questionId}/answers')->controller(AnswersController::class)->name('answers.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                    });
                });
                Route::prefix('answers')->controller(AnswersController::class)->name('answers.')->middleware('throttle:10,1')->group(function () {
                    Route::post('update', 'update')->name('update');
                    Route::post('delete', 'delete')->name('delete');
                });

                # Offline Quizzes
                Route::prefix('offline-quizzes')->controller(OfflineQuizzesController::class)->name('offline-quizzes.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                        Route::post('{uuid}/reset', 'resetStudentOfflineQuiz')->name('resetStudentOfflineQuiz');
                        Route::post('{uuid}/scores/insert', 'insertScores')->name('scores.insert');
                    });
                    Route::get('{uuid}/scores', 'scores')->name('scores');
                    Route::get('{uuid}/reports', 'reports')->name('reports');
                    Route::get('{uuid}/students-taken-offline-quiz', 'studentsTakenOfflineQuiz')->name('studentsTakenOfflineQuiz');
                    Route::get('{uuid}/students-not-taken-offline-quiz', 'studentsNotTakenOfflineQuiz')->name('studentsNotTakenOfflineQuiz');
                });

                # Assignments
                Route::prefix('assignments')->controller(AssignmentsController::class)->name('assignments.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('{uuid}', 'details')->name('details');
                    Route::get('files/{fileId}/download', 'downloadFile')->name('files.download');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                        Route::post('{uuid}/upload', 'uploadFile')->name('files.upload');
                        Route::post('files/delete', 'deleteFile')->name('files.delete');
                        Route::post('{uuid}/students/{studentUuid}/feedback', 'feedback')->name('feedback');
                        Route::post('{uuid}/students/{studentUuid}/reset', 'resetStudentAssignment')->name('resetStudentAssignment');
                    });
                    Route::get('{uuid}/reports', 'reports')->name('reports');
                    Route::get('{uuid}/students/{studentUuid}/review', 'review')->name('review');
                    Route::get('{uuid}/students-took-assignment', 'studentsTookAssignment')->name('studentsTookAssignment');
                    Route::get('{uuid}/students-havenot-taken-assignment', 'studentsHavenotTakenAssignment')->name('studentsHavenotTakenAssignment');
                    Route::get('submissions/{fileId}/download', 'downloadSubmission')->name('submissions.download');
                    Route::get('submissions/{fileId}/view', 'viewSubmission')->name('submissions.view');
                });
                # End Activities

                # Start Finance Managment
                Route::prefix('verify-pin')->controller(VerifyFinancalPinController::class)->name('verify-pin.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'verify')->middleware('throttle:5,1')->name('insert');
                });

                # Fees
                Route::prefix('fees')->controller(FeesController::class)->name('fees.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                    });
                    Route::get('{uuid}/reports', 'reports')->middleware('restrict.financials')->name('reports');
                    Route::get('{uuid}/students-paid-fees', 'studentsPaidFee')->name('studentsPaidFee');
                    Route::get('{uuid}/students-havenot-pay-fee', 'studentsHavenotPaidFee')->name('studentsHavenotPaidFee');
                    Route::get('{uuid}/students-without-fee', 'studentsWithoutFee')->name('studentsWithoutFee');
                });

                # Student Fees
                Route::prefix('student-fees')->controller(StudentFeesController::class)->name('student-fees.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('update', 'update')->name('update');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('delete-selected', 'deleteSelected')->name('deleteSelected');
                    });
                });

                # Invoices
                Route::prefix('invoices')->controller(InvoicesController::class)->name('invoices.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/verify-pin', 'verifyPin')->middleware('throttle:5,1')->name('verify-pin');
                    Route::get('{uuid}/print', 'print')->name('print');
                    Route::get('create', 'create')->name('create');
                    Route::get('{uuid}', 'preview')->name('preview');
                    Route::get('{uuid}/edit', 'edit')->name('edit');
                    Route::middleware('throttle:10,1')->group(function () {
                        Route::post('insert', 'insert')->name('insert');
                        Route::post('{uuid}/update', 'update')->name('update');
                        Route::post('{uuid}/payment', 'payment')->name('payment');
                        Route::post('{uuid}/refund', 'refund')->name('refund');
                        Route::post('delete', 'delete')->name('delete');
                        Route::post('cancel', 'cancel')->name('cancel');
                    });
                    Route::get('{uuid}/transactions', 'transactions')->name('transactions');
                });

                # Transactions
                Route::prefix('transactions')->controller(TransactionsController::class)->name('transactions.')->group(function () {
                    Route::get('/', 'index')->name('index');
                });
                # End Finance Managment
            });
        });
    }
);
