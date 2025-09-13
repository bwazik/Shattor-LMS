<?php

namespace App\Services;

use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SessionService
{
    public function getUserSessions($guard, $userId)
    {
        return Cache::remember("user_sessions_{$guard}_{$userId}", 300, function () use ($guard, $userId) {
            $sessionKeyPrefix = 'login_' . $guard . '_';

            $allSessions = DB::table('sessions')->where('user_id', $userId)->get();

            $sessions = $allSessions->filter(function ($session) use ($sessionKeyPrefix) {
                try {
                    $decodedPayload = unserialize(base64_decode($session->payload));
                    return collect(array_keys($decodedPayload))->contains(function ($key) use ($sessionKeyPrefix) {
                        return strpos($key, $sessionKeyPrefix) === 0;
                    });
                } catch (\Exception $e) {
                    return false;
                }
            });

            return $sessions->map(function ($session) use ($guard, $userId) {
                $agent = new Agent();
                $agent->setUserAgent($session->user_agent);
                $browser = $agent->browser() ?: trans('account.unknown');
                $platform = $agent->platform() ?: trans('account.unknown');
                $device = $agent->device() ?: trans('account.unknown');

                $platformMap = [
                    'Windows' => trans('account.windows'),
                    'Mac' => trans('account.macos'),
                    'iOS' => trans('account.ios'),
                    'Android' => trans('account.android'),
                ];
                $translatedPlatform = $platformMap[$platform] ?? $platform;

                $browserMap = [
                    'Chrome' => 'Chrome',
                    'Firefox' => 'Firefox',
                    'Safari' => 'Safari',
                    'Edge' => 'Edge',
                ];
                $browserKey = array_key_exists($browser, $browserMap) ? strtolower($browser) : trans('account.unknown');
                $translatedBrowser = trans("account.$browserKey");

                $platformIcons = [
                    'Windows' => 'ri-computer-line',
                    'Mac' => 'ri-mac-line',
                    'iOS' => 'ri-apple-line',
                    'Android' => 'ri-android-line',
                ];
                $icon = $platformIcons[$platform] ?? 'ri-global-line';

                $location = Cache::remember("ip_location_{$session->ip_address}", now()->addHours(24), function () use ($session) {
                    if ($session->ip_address && $session->ip_address !== '127.0.0.1') {
                        try {
                            $response = Http::get("http://ip-api.com/json/{$session->ip_address}");
                            $data = $response->json();
                            return $data['country'] ?? trans('account.unknown');
                        } catch (\Exception $e) {
                            Log::error('Geolocation failed', ['ip' => $session->ip_address, 'error' => $e->getMessage()]);
                        }
                    }
                    return trans('account.unknown');
                });

                $lastActivityTime = Carbon::createFromTimestamp($session->last_activity);
                $lastActivity = now()->diffInMinutes($lastActivityTime) < 5
                    ? trans('account.online')
                    : $lastActivityTime->diffForHumans();

                return [
                    'browser' => $translatedBrowser . ' ' . trans("account.on") . ' ' . $translatedPlatform,
                    'device' => $device,
                    'location' => $location,
                    'last_activity' => $lastActivity,
                    'icon' => $icon,
                ];
            });
        });
    }

    public function getUserDevices($guard, $userId)
    {
        return Cache::remember("user_devices_{$guard}_{$userId}", 300, function () use ($guard, $userId) {
            $devices = DB::table('user_devices')
                ->where('user_id', $userId)
                ->where('guard', $guard)
                ->get(['id', 'device_id', 'device_fingerprint', 'user_agent', 'last_ip', 'last_used_at']);

            return $devices->map(function ($device) use ($guard, $userId) {
                $agent = new Agent();
                $agent->setUserAgent($device->user_agent);
                $platform = $agent->platform() ?: trans('account.unknown');
                $deviceName = $agent->device() ?: trans('account.unknown');

                $platformMap = [
                    'Windows' => trans('account.windows'),
                    'Mac' => trans('account.macos'),
                    'iOS' => trans('account.ios'),
                    'Android' => trans('account.android'),
                ];
                $translatedPlatform = $platformMap[$platform] ?? $platform;

                $platformIcons = [
                    'Windows' => 'ri-computer-line',
                    'Mac' => 'ri-mac-line',
                    'iOS' => 'ri-apple-line',
                    'Android' => 'ri-android-line',
                ];
                $icon = $platformIcons[$platform] ?? 'ri-device-line';

                $location = Cache::remember("ip_location_{$device->last_ip}", now()->addHours(24), function () use ($device) {
                    if ($device->last_ip && $device->last_ip !== '127.0.0.1') {
                        try {
                            $response = Http::get("http://ip-api.com/json/{$device->last_ip}");
                            $data = $response->json();
                            return $data['country'] ?? trans('account.unknown');
                        } catch (\Exception $e) {
                            Log::error('Geolocation failed', ['ip' => $device->last_ip, 'error' => $e->getMessage()]);
                        }
                    }
                    return trans('account.unknown');
                });

                $lastActivity = Carbon::parse($device->last_used_at)->diffForHumans();
                $sessionKeyPrefix = 'login_' . $guard . '_';
                $sessions = DB::table('sessions')->where('user_id', $userId)->get();
                foreach ($sessions as $session) {
                    try {
                        $decodedPayload = unserialize(base64_decode($session->payload));
                        $hasGuardKey = collect(array_keys($decodedPayload))->contains(function ($key) use ($sessionKeyPrefix) {
                            return strpos($key, $sessionKeyPrefix) === 0;
                        });
                        if ($hasGuardKey) {
                            $sessionDeviceId = $decodedPayload['device_id'] ?? null;
                            if ($sessionDeviceId === $device->device_id) {
                                $lastActivityTime = Carbon::createFromTimestamp($session->last_activity);
                                $lastActivity = now()->diffInMinutes($lastActivityTime) < 5
                                    ? trans('account.online')
                                    : $lastActivityTime->diffForHumans();
                                break;
                            }
                            // $sessionFingerprint = hash('sha256', $session->user_agent . '|' . $session->ip_address);
                            // if ($sessionFingerprint === $device->device_fingerprint) {
                            //     $lastActivityTime = Carbon::createFromTimestamp($session->last_activity);
                            //     $lastActivity = now()->diffInMinutes($lastActivityTime) < 5
                            //         ? trans('account.online')
                            //         : $lastActivityTime->diffForHumans();
                            //     break;
                            // }
                        }
                    } catch (\Exception $e) {
                        Log::error('Session payload decode failed', [
                            'session_id' => $session->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return [
                    'id' => $device->id,
                    'platform' => $translatedPlatform,
                    'device' => $deviceName,
                    'location' => $location,
                    'last_activity' => $lastActivity,
                    'icon' => $icon,
                ];
            });
        });
    }
}
