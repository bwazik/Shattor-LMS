<?php

namespace App\Http\Requests\Auth;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->route('guard') === 'parent') {
            return [
                'student_phone' => ['required', 'numeric', 'regex:/^(010|011|012|015)\d{8}$/'],
                'parent_phone' => ['required', 'numeric', 'regex:/^(010|011|012|015)\d{8}$/'],
            ];
        }

        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate($guard): void
    {
        $this->ensureIsNotRateLimited();

        if ($guard === 'parent') {
            $student = Student::where('phone', $this->input('student_phone'))->first();

            if (! $student || ! $student->parent || $student->parent->phone !== $this->input('parent_phone')) {
                RateLimiter::hit($this->throttleKey());

                Log::warning('Failed parent login attempt', [
                    'student_phone' => $this->input('student_phone'),
                    'parent_phone' => $this->input('parent_phone'),
                    'ip' => request()->ip(),
                ]);

                Carbon::setLocale('ar');
                $this->whatsappService->sendMessage('01098617164', 'failed_login_attempt', [
                    'username' => 'Parent of: ' . $this->input('student_phone'),
                    'guard' => $guard,
                    'ip' => request()->ip(),
                    'date' => now()->translatedFormat('l j F Y'),
                    'time' => now()->translatedFormat('h:i A'),
                ], true);

                throw ValidationException::withMessages([
                    'student_phone' => trans('auth.failed'),
                ]);
            }

            Auth::guard($guard)->login($student->parent);
        } else {
            if (!Auth::guard($guard)->attempt($this->only('username', 'password'), $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                Log::warning('Failed login attempt', [
                    'username' => $this->input('username'),
                    'guard' => $guard,
                    'ip' => request()->ip(),
                ]);

                Carbon::setLocale('ar');
                $this->whatsappService->sendMessage('01098617164', 'failed_login_attempt', [
                    'username' => $this->input('username'),
                    'guard' => $guard,
                    'ip' => request()->ip(),
                    'date' => now()->translatedFormat('l j F Y'),
                    'time' => now()->translatedFormat('h:i A'),
                ], true);

                throw ValidationException::withMessages([
                    'username' => trans('auth.failed'),
                ]);
            }
        }

        // Check and manage devices for non-web guards
        $user = Auth::guard($guard)->user();

        if ($guard !== 'web' && !$user->is_active) {
            Auth::guard($guard)->logout();
            RateLimiter::hit($this->throttleKey());

            Log::warning('Inactive or soft-deleted user login attempt', [
                'username' => $this->input('username'),
                'guard' => $guard,
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => trans('auth.inactive'),
            ]);
        }

        // Get or create device ID from cookie
        $deviceId = $this->getOrCreateDeviceId();

        // Create device fingerprint using device_id + user_agent
        $deviceFingerprint = hash('sha256', $deviceId . '|' . request()->userAgent());

        if ($guard === 'student') {
            // Enforce one-account-per-device for students in simple mode
            $existingDevice = DB::table('user_devices')
                ->where('device_id', $deviceId)
                ->where('guard', 'student')
                ->where('user_id', '!=', $user->id)  // Check for different user
                ->first();

            if ($existingDevice) {
                Auth::guard($guard)->logout();
                Log::warning('Device attempted login to multiple student accounts', [
                    'username' => $this->input('username'),
                    'user_id' => $user->id,
                    'user_phone' => $user->phone,
                    'device_id' => $deviceId,
                    'existing_user_id' => $existingDevice->user_id,
                    'ip' => request()->ip(),
                ]);

                // Notify admin
                Carbon::setLocale('ar');
                $this->whatsappService->sendMessage(
                    '01098617164',
                    'deviceAlreadyUsed',
                    [
                        'username' => $this->input('username'),
                        'device_id' => $deviceId,
                        'ip' => request()->ip(),
                        'date' => now()->translatedFormat('l j F Y'),
                        'time' => now()->translatedFormat('h:i A'),
                    ],
                    true
                );

                throw ValidationException::withMessages([
                    'username' => trans('auth.deviceAlreadyUsed'),  // "This device is already tied to another account."
                ]);
            }
        }

        $deviceCount = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('guard', $guard)
            ->count();

        $isAuthorized = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('guard', $guard)
            ->where('device_id', $deviceId)
            ->exists();

        if ($guard === 'web') {
            // admin: unlimited devices & sessions → no restriction
        } elseif ($guard === 'teacher') {
            // teacher: unlimited devices & sessions → no restriction
        } else {
            if ($deviceCount >= 2 && !$isAuthorized) {
                Auth::guard($guard)->logout();
                Log::warning('Too many devices attempted login', [
                    'username' => $this->input('username'),
                    'guard' => $guard,
                    'ip' => request()->ip(),
                    'fingerprint' => $deviceFingerprint,
                ]);
                throw ValidationException::withMessages([
                    'username' => trans('auth.tooManyDevices'),
                ]);
            }

            // Invalidate other sessions for this user and guard
            $sessionKeyPrefix = 'login_' . $guard . '_';
            $currentSessionId = session()->getId();
            $sessions = DB::table('sessions')->where('user_id', $user->id)->get();

            foreach ($sessions as $session) {
                if ($session->id === $currentSessionId) {
                    continue;
                }
                try {
                    $decodedPayload = unserialize(base64_decode($session->payload));
                    foreach (array_keys($decodedPayload) as $key) {
                        if (strpos($key, $sessionKeyPrefix) === 0) {
                            DB::table('sessions')->where('id', $session->id)->delete();
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Session payload decode failed', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Send WhatsApp message for new device (exclude parent guard)
        // if (!$isAuthorized && $guard !== 'parent' && !empty($user->phone)) {
        //     $name = $guard === 'web'
        //         ? explode(' ', trim($user->name))[0]
        //         : explode(' ', trim($user->getTranslation('name', 'ar')))[0];

        //     $name = $guard === 'teacher' ? "مستر {$name}" : $name;

        //     Carbon::setLocale('ar');

        //     $this->whatsappService->sendMessage(
        //         $user->phone,
        //         'ؤ',
        //         [
        //             'name' => $name,
        //             'date' => now()->translatedFormat('l j F Y'),
        //             'time' => now()->translatedFormat('h:i A'),
        //         ],
        //         true
        //     );
        // }

        if (!empty($user->phone)) {
            $name = $guard === 'teacher' ? "مستر {$user->name}" : $user->name;

            Carbon::setLocale('ar');

            $this->whatsappService->sendMessage('01098617164', 'login_notification', [
                'name' => $name,
                'date' => now()->translatedFormat('l j F Y'),
                'time' => now()->translatedFormat('h:i A'),
            ], true);
        }

        // Update or add device
        DB::table('user_devices')->updateOrInsert(
            [
                'user_id' => $user->id,
                'guard' => $guard,
                'device_id' => $deviceId,
            ],
            [
                'device_fingerprint' => $deviceFingerprint,
                'user_agent' => request()->userAgent(),
                'last_ip' => request()->ip(),
                'last_used_at' => now(),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );

        // Store device_id in session for session matching
        session(['device_id' => $deviceId]);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $key = $this->has('student_phone') ? $this->input('student_phone') : $this->input('username');
        return Str::transliterate(Str::lower($key) . '|' . $this->ip());
    }

    private function getOrCreateDeviceId(): string
    {
        $cookieName = 'device_id';
        $deviceId = request()->cookie($cookieName);

        if (!$deviceId) {
            // Generate new device ID
            $deviceId = 'dev_' . time() . '_' . bin2hex(random_bytes(8));

            // Set cookie for 10 years
            cookie()->queue(cookie(
                $cookieName,
                $deviceId,
                60 * 24 * 365, // 1 years in minutes
                '/', // path
                null, // domain
                request()->isSecure(), // secure (HTTPS only)
                true, // httpOnly
                false, // raw
                'Lax' // sameSite
            ));

            Log::info('New device ID created', [
                'device_id' => $deviceId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $deviceId;
    }
}
