<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Grade;
use App\Models\Coupon;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\ZoomAccount;
use Illuminate\Http\Request;
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
use App\Services\QRCodeService;

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
        $student = Student::select('uuid')->where('uuid', $uuid)->firstOrFail();
        return response()->json(['uuid' => $student->uuid]);
    }

    public function editPersonalInfo()
    {
        $model = $this->mapping['model'];
        $cacheKey = "account:{$this->guard}:{$this->userId}:personal";
        $ttl = 3600; // 1 hour

        $qrcode = $this->qrCodeService->generateQRCode('student', $this->userUuid);

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
                    ->select('id', 'username', 'name', 'phone', 'email', 'gender', 'birth_date', 'grade_id', 'parent_id')
                    ->findOrFail($this->userId);

                $groupIds = $user->groups->pluck('uuid')->toArray();
                $teacherIds = $user->teachers->pluck('uuid')->toArray();

                return [
                    'teachers' => $user->teachers->mapWithKeys(fn($teacher) => [$teacher->uuid => $teacher->name]),
                    'groups' => $user->groups->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->teacher->name]),
                    'student' => $user->setAttribute('groups', implode(',', $groupIds))
                        ->setAttribute('teachers', implode(',', $teacherIds)),
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
}
