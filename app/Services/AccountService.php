<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\MyParent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Transaction;
use App\Models\ZoomAccount;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Hash;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class AccountService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $guardMappings = [
        'teacher' => [
            'model' => Teacher::class,
        ],
        'student' => [
            'model' => Student::class,
        ],
        'parent' => [
            'model' => MyParent::class,
        ],
    ];

    public function updatePersonalInfo(string $guard, int $userId, array $request): array
    {
        return $this->executeTransaction(function () use ($guard, $userId, $request) {
            if ($guard === 'teacher') {
                $teacher = Teacher::findOrFail($userId);

                $teacher->update([
                    'username' => $request['username'],
                    'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                    'phone' => $request['phone'],
                    'email' => $request['email'],
                    'subject_id' => $request['subject_id'],
                ]);

                $teacher->grades()->sync($request['grades'] ?? []);
            } elseif ($guard === 'student') {
                $student = Student::findOrFail($userId);

                $student->update([
                    'name' => ['en' => $request['name_en']],
                    'username' => $request['username'],
                    'email' => $request['email'],
                    'birth_date' => $request['birth_date'],
                    'gender' => $request['gender'],
                ]);
            } elseif ($guard === 'parent') {
                $parent = MyParent::findOrFail($userId);

                $parent->update([
                    'username' => $request['username'],
                    'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                    'phone' => $request['phone'],
                    'email' => $request['email'],
                    'gender' => $request['gender'],
                ]);
            }

            return $this->successResponse(trans('toasts.personalInfoUpdated'));
        });
    }

    public function updatePassword(string $guard, int $userId, array $request): array
    {
        return $this->executeTransaction(function () use ($guard, $userId, $request) {
            $mapping = $this->guardMappings[$guard];
            $model = $mapping['model'];
            $user = $model::findOrFail($userId);

            if (!Hash::check($request['currentPassword'], $user->password)) {
                return $this->errorResponse(trans('toasts.invalidCurrentPassword'));
            }

            $user->update([
                'password' => Hash::make($request['newPassword'])
            ]);

            return $this->successResponse(trans('toasts.passwordUpdated'));
        });
    }

    public function updateZoomAccount(string $guard, int $userId, array $request): array
    {
        return $this->executeTransaction(function () use ($guard, $userId, $request) {
            $mapping = $this->guardMappings[$guard];
            $model = $mapping['model'];
            $user = $model::findOrFail($userId);

            ZoomAccount::updateOrCreate(
                ['teacher_id' => $user->id],
                [
                    'account_id' => $request['accountId'],
                    'client_id' => $request['clientId'],
                    'client_secret' => $request['clientSecret'],
                ]
            );

            return $this->successResponse(trans('toasts.zoomAccountUpdated'));
        });
    }

    public function getCouponsForDatatable($couponsQuery)
    {
        return datatables()->eloquent($couponsQuery)
            ->addIndexColumn()
            ->editColumn('is_used', fn($row) => formatUsedStatus($row->is_used))
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->filterColumn('is_used', fn($query, $keyword) => filterUsedStatus($query, $keyword))
            ->rawColumns(['is_used'])
            ->make(true);
    }

    public function redeemCoupon(string $guard, int $userId, array $request): array
    {
        return $this->executeTransaction(function () use ($guard, $userId, $request) {
            $mapping = $this->guardMappings[$guard];
            $model = $mapping['model'];

            $coupon = Coupon::where('code', $request['code'])
                ->unused()
                ->where("{$guard}_id", $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $user = $model::findOrFail($userId);
            $user->increment('balance', $coupon->amount);

            $coupon->update(['is_used' => true]);

            Transaction::create([
                'type' => 4,
                "{$guard}_id" => $userId,
                'amount' => $coupon->amount,
                'balance_after' => $user->balance ?? 0,
                'date' => now()->format('Y-m-d'),
            ]);

            return $this->successResponse(trans('toasts.couponRedeemed', ['amount' => $coupon->amount]));
        }, trans('toasts.invalidCoupon'));
    }
}
