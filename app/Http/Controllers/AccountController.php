<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Grade;
use App\Models\Coupon;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\GradeFee;
use App\Models\MyParent;
use App\Models\ZoomAccount;
use Illuminate\Http\Request;
use App\Services\QRCodeService;
use App\Services\AccountService;
use App\Services\SessionService;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\ProfilePicRequest;
use App\Traits\DatabaseTransactionTrait;
use App\Http\Requests\ZoomAccountRequest;
use App\Services\Admin\FileUploadService;
use App\Http\Requests\PersonalDataRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\SecurityCodeUpdateRequest;

class AccountController extends Controller
{
    use DatabaseTransactionTrait, ServiceResponseTrait;

    protected $qrCodeService;
    protected $profilePicService;
    protected $accountService;
    protected $sessionService;
    protected $guard;
    protected $mapping;
    protected $userId;
    protected $userUuid;
    protected $guardMappings = [
        'teacher' => [
            'model' => Teacher::class,
            'view_prefix' => 'teacher.account',
            'profile_pic_path' => 'teachers',
        ],
        'student' => [
            'model' => Student::class,
            'view_prefix' => 'student.account',
            'profile_pic_path' => 'students',
        ],
        'parent' => [
            'model' => MyParent::class,
            'view_prefix' => 'parent.account',
        ],
    ];

    public function __construct(QRCodeService $qrCodeService, FileUploadService $profilePicService, AccountService $accountService, SessionService $sessionService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->profilePicService = $profilePicService;
        $this->accountService = $accountService;
        $this->sessionService = $sessionService;
        $this->guard = Auth::getDefaultDriver();
        $this->mapping = $this->guardMappings[$this->guard];
        $this->userId = Auth::guard($this->guard)->id();
        $this->userUuid = optional(Auth::guard($this->guard)->user())->uuid;
    }

    public function scanQRCode($uuid, Request $request)
    {
        $model = $this->mapping['model'];

        $user = $model::select('uuid')->where('uuid', $uuid)->firstOrFail();
        return response()->json(['uuid' => $user->uuid]);
    }

    public function editPersonalInfo()
    {
        $model = $this->mapping['model'];
        $cacheKey = "account:{$this->guard}:{$this->userId}:personal";
        $ttl = 3600; // 1 hour

        $qrcode = $this->qrCodeService->generateQRCode($this->guard, $this->userUuid);

        $data = Cache::remember($cacheKey, $ttl, function () use ($model) {
            if ($this->guard === 'teacher') {
                $user = $model::query()
                    ->with('grades')
                    ->select('id', 'username', 'name', 'phone', 'email', 'subject_id', 'plan_id')
                    ->findOrFail($this->userId);

                $subjects = Cache::remember('subjects', 86400, fn() => Subject::query()
                    ->select('id', 'name')
                    ->orderBy('id')
                    ->pluck('name', 'id')
                    ->toArray());

                $grades = Cache::remember('grades', 86400, fn() => Grade::query()
                    ->select('id', 'name')
                    ->orderBy('id')
                    ->pluck('name', 'id')
                    ->toArray());

                $gradeIds = $user->grades->pluck('id')->toArray();

                $plan = Plan::find($user->plan_id);
                $currentStudents = $user->students()->count();
                $currentGroups = $user->groups()->count();

                return [
                    'subjects' => $subjects,
                    'grades' => $grades,
                    'remainingStudents' => $plan ? max(0, $plan->student_limit - $currentStudents) : 0,
                    'remainingGroups' => $plan ? max(0, $plan->group_limit - $currentGroups) : 0,
                    'teacher' => $user->setAttribute('grades', implode(',', $gradeIds)),
                ];
            } elseif ($this->guard === 'student') {
                $user = $model::query()
                    ->with(['grade', 'parent', 'teachers', 'groups.teacher'])
                    ->select('id', 'username', 'name', 'phone', 'email', 'gender', 'birth_date', 'grade_id', 'specialization', 'parent_id')
                    ->findOrFail($this->userId);

                $groupIds = $user->groups->pluck('uuid')->toArray();
                $teacherIds = $user->teachers->pluck('uuid')->toArray();

                return [
                    'teachers' => $user->teachers->mapWithKeys(fn($teacher) => [$teacher->uuid => $teacher->name]),
                    'groups' => $user->groups->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->teacher->name]),
                    'student' => $user->setAttribute('groups', implode(',', $groupIds))
                        ->setAttribute('teachers', implode(',', $teacherIds)),
                ];
            } elseif ($this->guard === 'parent') {
                $user = $model::query()
                    ->with([
                        'students:id,username,name,phone,email,gender,birth_date,grade_id,parent_id',
                        'students.grade:id,name',
                    ])
                    ->select('id', 'username', 'name', 'phone', 'email', 'gender')
                    ->findOrFail($this->userId);

                return [
                    'parent' => $user,
                ];
            }
        });

        return view("{$this->mapping['view_prefix']}.personal", compact('qrcode', 'data'));
    }

    public function updateProfilePic(ProfilePicRequest $request)
    {
        $result = $this->profilePicService->updateProfilePic($request, $this->mapping['model'], $this->userId, $this->mapping['profile_pic_path']);

        return $this->conrtollerJsonResponse($result, "account:{$this->guard}:{$this->userId}:personal");
    }

    public function updatePersonalInfo(PersonalDataRequest $request)
    {
        $result = $this->accountService->updatePersonalInfo($this->guard, $this->userId, $request->validated());

        return $this->conrtollerJsonResponse($result, "account:{$this->guard}:{$this->userId}:personal");
    }

    public function securityIndex()
    {
        $zoomAccount = null;
        if ($this->guard === 'teacher') {
            $zoomAccount = Cache::remember("zoom_account_{$this->userId}", 3600, function () {
                $account = ZoomAccount::where('teacher_id', $this->userId)
                    ->select('account_id', 'client_id', 'client_secret')
                    ->first();
                return $account ? [
                    'accountId' => $account->account_id,
                    'clientId' => $account->client_id,
                    'clientSecret' => $account->client_secret,
                ] : null;
            });
        }
        $sessions = $this->sessionService->getUserSessions($this->guard, $this->userId);
        $devices = $this->sessionService->getUserDevices($this->guard, $this->userId);

        return view("{$this->mapping['view_prefix']}.security", compact('zoomAccount', 'sessions', 'devices'));
    }

    public function updatePassword(PasswordUpdateRequest $request)
    {
        $result = $this->accountService->updatePassword($this->guard, $this->userId, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function updateSecurityCode(SecurityCodeUpdateRequest $request)
    {
        $result = $this->accountService->updateSecurityCode($this->userId, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function updateZoomAccount(ZoomAccountRequest $request)
    {
        $result = $this->accountService->updateZoomAccount($this->guard, $this->userId, $request->validated());

        return $this->conrtollerJsonResponse($result, "zoom_account_{$this->userId}");
    }

    public function getCoupons(Request $request)
    {
        $couponsQuery = Coupon::query()
            ->select('id', 'code', 'is_used', 'amount')
            ->where("{$this->guard}_id", $this->userId)
            ->used()
            ->whereNull($this->guard === 'teacher' ? 'student_id' : 'teacher_id');

        if ($request->ajax()) {
            return $this->accountService->getCouponsForDatatable($couponsQuery);
        }

        return view("{$this->mapping['view_prefix']}.coupons");
    }

    public function redeemCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|min:3|max:10|exists:coupons,code',
        ]);

        $result = $this->accountService->redeemCoupon($this->guard, $this->userId, $validated);

        return $this->conrtollerJsonResponse($result);
    }

    public function feesPricingIndex()
    {
        $grades = Grade::whereHas('teachers', fn($q) => $q->where('teacher_id', $this->userId))
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        $startYear = 2025;
        $endYear = 2026;
        $gradeFees = GradeFee::where('teacher_id', $this->userId)
            ->where(function ($query) use ($startYear, $endYear) {
                $query->whereBetween('month', ["$startYear-08", "$endYear-07"])
                    ->orWhereNull('month');
            })
            ->select('grade_id', 'specialization', 'applies_to_all_specializations', 'amount', 'month')
            ->get()
            ->groupBy(['month', 'grade_id']);

        $months = collect();
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::create($startYear, 8, 1)->addMonths($i);
            $months->push([
                'name' => $date->format('F Y'),
                'key' => $date->format('Y-m'),
                'arabic_name' => $this->getArabicMonthName($date->month) . ' ' . $date->year,
            ]);
        }

        $year = $startYear;
        $specializations = [
            1 => trans('main.scientific'),
            2 => trans('main.literary'),
        ];

        return view("{$this->mapping['view_prefix']}.fees", compact('grades', 'gradeFees', 'months', 'year', 'specializations'));
    }

    public function updateFeesPricing(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'fees' => ['required', 'array'],
            'fees.*.grade_id' => ['required', 'integer', 'exists:grades,id'],
            'fees.*.amount' => ['required', 'numeric', 'min:0'],
            'fees.*.specialization' => ['nullable', 'integer', 'in:1,2'],
            'fees.*.applies_to_all_specializations' => ['nullable', 'boolean'],
        ]);

        return $this->executeTransaction(function () use ($validated) {
            $month = $validated['month'];
            $teacherId = $this->userId;

            $validGradeIds = Grade::whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId))
                ->pluck('id')
                ->toArray();

            $feesByGrade = collect($validated['fees'])->groupBy('grade_id');

            foreach ($feesByGrade as $gradeId => $gradeFees) {
                if (!in_array($gradeId, $validGradeIds)) {
                    return $this->errorResponse(trans('toasts.ownershipError'));
                }

                GradeFee::where('teacher_id', $teacherId)
                    ->where('grade_id', $gradeId)
                    ->where('month', $month)
                    ->delete();

                $hasAppliedToAll = collect($gradeFees)->contains(function ($fee) {
                    return isset($fee['applies_to_all_specializations']) && $fee['applies_to_all_specializations'];
                });

                if ($hasAppliedToAll) {
                    $appliedToAllFee = collect($gradeFees)->first(function ($fee) {
                        return isset($fee['applies_to_all_specializations']) && $fee['applies_to_all_specializations'];
                    });

                    if ($appliedToAllFee && $appliedToAllFee['amount'] > 0) {
                        GradeFee::create([
                            'teacher_id' => $teacherId,
                            'grade_id' => $gradeId,
                            'month' => $month,
                            'specialization' => null,
                            'applies_to_all_specializations' => true,
                            'amount' => $appliedToAllFee['amount'],
                        ]);
                    }
                } else {
                    foreach ($gradeFees as $fee) {
                        if (!$fee['amount'] || $fee['amount'] <= 0) {
                            continue;
                        }

                        $specialization = isset($fee['specialization']) ? (int) $fee['specialization'] : null;

                        if (!$specialization) {
                            continue;
                        }

                        GradeFee::create([
                            'teacher_id' => $teacherId,
                            'grade_id' => $gradeId,
                            'month' => $month,
                            'specialization' => $specialization,
                            'applies_to_all_specializations' => false,
                            'amount' => $fee['amount'],
                        ]);
                    }
                }
            }

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/fees.fees')]));
        });
    }

    private function getArabicMonthName($month)
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];
        return $months[$month] ?? 'N/A';
    }
}
