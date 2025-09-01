<?php

namespace App\Http\Requests\Auth;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Services\WhatsappService;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate($guard): void
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::guard($guard)->attempt($this->only('username', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            Log::warning('Failed login attempt', [
                'username' => $this->input('username'),
                'guard' => $guard,
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
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

        $deviceFingerprint = hash('sha256', request()->userAgent() . '|' . request()->ip());
        $deviceCount = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('guard', $guard)
            ->count();

        $isAuthorized = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('guard', $guard)
            ->where('device_fingerprint', $deviceFingerprint)
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
        if (!$isAuthorized && $guard !== 'parent' && !empty($user->phone)) {
            $whatsAppService = app(WhatsAppService::class);
            $whatsAppService->sendMessage($user->phone, 'new_device_login',
                [
                    'name' => explode(' ', trim($user->name))[0],
                    'date' => now()->translatedFormat('l j F Y'),
                    'time' => now()->translatedFormat('h:i A'),
                ]
            );
        }

        // Update or add device
        DB::table('user_devices')->updateOrInsert(
            [
                'user_id' => $user->id,
                'guard' => $guard,
                'device_fingerprint' => $deviceFingerprint,
            ],
            [
                'user_agent' => request()->userAgent(),
                'last_ip' => request()->ip(),
                'last_used_at' => now(),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );

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
        return Str::transliterate(Str::lower($this->string('username')) . '|' . $this->ip());
    }
}
