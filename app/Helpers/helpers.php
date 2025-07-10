<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


if (!function_exists('isActiveRoute')) {
    function isActiveRoute($routes)
    {
        if (is_array($routes)) {
            foreach ($routes as $route) {
                if (Route::currentRouteName() === $route) {
                    return true;
                }
            }
        } elseif (Route::currentRouteName() === $routes) {
            return true;
        }

        return false;
    }
}

if (!function_exists('pageTitle')) {
    function pageTitle($key) {
        return trans($key) . ' - ' . trans('layouts/sidebar.platformName');
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($value) {
        return number_format($value, 2, '.', ',');
    }
}

if(!function_exists('getDayName')) {
    function getDayName(int $dayNumber)
    {
        $dayMapping = [
            1 => trans('main.weekdays.1'),
            2 => trans('main.weekdays.2'),
            3 => trans('main.weekdays.3'),
            4 => trans('main.weekdays.4'),
            5 => trans('main.weekdays.5'),
            6 => trans('main.weekdays.6'),
            7 => trans('main.weekdays.7'),
        ];

        return $dayMapping[$dayNumber] ?? '-';
    }
}

if(!function_exists('mapDaysToNames')) {
    function mapDaysToNames(array $days)
    {
        return array_map(function ($day) {
            return getDayName($day);
        }, $days);
    }
}

if(!function_exists('isoFormat')) {
    function isoFormat(string $value)
    {
        return Carbon::parse($value, 'Africa/Cairo')->isoFormat('dddd D MMMM h:mm A');
    }
}

if(!function_exists('humanFormat')) {
    function humanFormat(string $value)
    {
        return Carbon::parse($value, 'Africa/Cairo')->format('Y-m-d H:i');
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return auth()->guard('web')->check();
    }
}

if (!function_exists('isTeacher')) {
    function isTeacher()
    {
        return auth()->guard('teacher')->check();
    }
}

if (!function_exists('isAssistant')) {
    function isAssistant()
    {
        return auth()->guard('assistant')->check();
    }
}

if (!function_exists('isStudent')) {
    function isStudent()
    {
        return auth()->guard('student')->check();
    }
}

if (!function_exists('isParent')) {
    function isParent()
    {
        return auth()->guard('parent')->check();
    }
}


if (!function_exists('filterByRelation')) {
    function filterByRelation($query, $relation, $column, $keyword)
    {
        $query->whereHas($relation, function ($q) use ($column, $keyword) {
            $q->where($column, 'LIKE', "%$keyword%");
        });
    }
}

if (!function_exists('filterByStatus')) {
    function filterByStatus($query, $keyword, $column = 'is_active')
    {
        $keyword = trim(mb_strtolower($keyword, 'UTF-8'));

        $activeKeywords = ['active', 'مفعل'];
        $inactiveKeywords = ['inactive', 'غير', 'غير مفعل'];

        if (Str::contains($keyword, $activeKeywords)) {
            $query->where($column, 1);
        } elseif (Str::contains($keyword, $inactiveKeywords)) {
            $query->where($column, 0);
        }
    }
}

if (!function_exists('filterByExemptionStatus')) {
    function filterByExemptionStatus($query, $keyword, $column = 'is_exempted')
    {
        $keyword = trim(mb_strtolower($keyword, 'UTF-8'));

        $activeKeywords = ['exempted', 'معفي'];
        $inactiveKeywords = ['notexempted', 'غير', 'غير معفي'];

        if (Str::contains($keyword, $activeKeywords)) {
            $query->where($column, 1);
        } elseif (Str::contains($keyword, $inactiveKeywords)) {
            $query->where($column, 0);
        }
    }
}


if (!function_exists('formatInvoiceStatus')) {
    function filterByInvoiceStatus($query, $keyword, $column = 'status')
    {
        $keyword = trim(strtolower($keyword));

        $statusMappings = [
            1 => ['pending', 'معلقة', 'قيد الإنتظار'],
            2 => ['paid', 'مدفوعة'],
            3 => ['overdue', 'متأخرة'],
            4 => ['canceled', 'ملغية']
        ];

        foreach ($statusMappings as $status => $keywords) {
            if (Str::contains($keyword, $keywords)) {
                $query->where($column, $status);
                break;
            }
        }

        return $query;
    }
}

if (!function_exists('filterUsedStatus')) {
    function filterUsedStatus($query, $keyword, $column = 'is_used')
    {
        $keyword = trim(mb_strtolower($keyword, 'UTF-8'));

        $activeKeywords = ['used', 'مستخدم'];
        $inactiveKeywords = ['available', 'متاح'];

        if (Str::contains($keyword, $activeKeywords)) {
            $query->where($column, 1);
        } elseif (Str::contains($keyword, $inactiveKeywords)) {
            $query->where($column, 0);
        }
    }
}

if (!function_exists('filterDetailsColumn')) {
    function filterDetailsColumn($query, $keyword, $secondaryField): void
    {
        $query->where(function ($q) use ($keyword, $secondaryField) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere($secondaryField, 'like', "%{$keyword}%");
        });
    }
}

if (!function_exists('generateSelectbox')) {
    function generateSelectbox($id): string
    {
        return
            '<td class="dt-checkboxes-cell">' .
                '<input type="checkbox" value="' . $id . '" class="dt-checkboxes form-check-input">' .
            '</td>';
    }
}

if (!function_exists('generateDetailsColumn')) {
    function generateDetailsColumn($name, $profilePic = null, $profilePath = 'storage/profiles', $secondaryText = null, $routeName = null, $id = null): string
    {
        $defaultPic = asset('assets/img/avatars/default.jpg');
        $picSrc = $profilePic ? asset("{$profilePath}/{$profilePic}") : $defaultPic;
        $profileLink = $routeName && $id ? route($routeName, $id) : '#';

        return
            '<div class="d-flex justify-content-start align-items-center">' .
                '<div class="avatar-wrapper">' .
                    '<div class="avatar avatar-sm me-3">' .
                        '<img src="' . $picSrc . '" alt="Profile Picture" class="rounded-circle">' .
                    '</div>' .
                '</div>' .
                '<div class="d-flex flex-column align-items-start">' .
                    '<a target="_blank" href="' . $profileLink . '" class="text-truncate text-heading"><p class="mb-0 fw-medium">' . $name . '</p></a>' .
                    '<small class="text-truncate">' . ($secondaryText ?? '-') . '</small>' .
                '</div>' .
            '</div>';
    }
}


if (!function_exists('formatActiveStatus')) {
    function formatActiveStatus($isActive): string
    {
        return $isActive
            ? '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('main.active') . '</span>'
            : '<span class="badge rounded-pill bg-label-secondary text-capitalized">' . trans('main.inactive') . '</span>';
    }
}

if (!function_exists('formatCorrectionStatus')) {
    function formatCorrectionStatus($isCorrect): string
    {
        return $isCorrect
            ? '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('main.correct') . '</span>'
            : '<span class="badge rounded-pill bg-label-danger text-capitalized">' . trans('main.wrong') . '</span>';
    }
}

if (!function_exists('formatExemptedStatus')) {
    function formatExemptedStatus($isExempted): string
    {
        return $isExempted
            ? '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('main.exempted') . '</span>'
            : '<span class="badge rounded-pill bg-label-secondary text-capitalized">' . trans('main.notexempted') . '</span>';
    }
}

if (!function_exists('formatUsedStatus')) {
    function formatUsedStatus($isUsed): string
    {
        return $isUsed
            ? '<span class="badge rounded-pill bg-label-secondary text-capitalize">' . trans('main.used') . '</span>'
            : '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.unused') . '</span>';
    }
}

if (!function_exists('formatTransactionType')) {
    function formatTransactionType($type): string
    {
        switch ($type) {
            case 1:
                return '<span class="badge rounded-pill bg-label-primary text-capitalize">' . trans('admin/invoices.invoice') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.payment') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('main.refund') . '</span>';
            case 4:
                return '<span class="badge rounded-pill bg-label-info text-capitalize">' . trans('admin/coupons.coupon') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }
}

if (!function_exists('formatPaymentMethod')) {
    function formatPaymentMethod($paymentMethod): string
    {
        switch ($paymentMethod) {
            case 1:
                return '<img width="30" height="30" src="' . asset('assets/img/brand/cash.png') . '" alt="' . trans('main.cash') .'">';
            case 2:
                return '<img width="30" height="30" src="' . asset('assets/img/brand/vodafone.png') . '" alt="' . trans('main.vodafoneCash') .'">';
            case 3:
                return '<img width="30" height="30" src="' . asset('assets/img/brand/instapay.png') . '" alt="' . trans('main.instapay') .'">';
            case 4:
                return '<img width="30" height="30" src="' . asset('assets/img/brand/wallet.png') . '" alt="' . trans('main.wallet') .'">';
            default:
                return 'N/A';
        }
    }
}

if (!function_exists('formatSubscriptionStatus')) {
    function formatSubscriptionStatus($status): string
    {
        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.active') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('main.canceled') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('main.expired') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }
}


if (!function_exists('formatLessonStatus')) {
    function formatLessonStatus($status): string
    {
        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('main.scheduled') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.completed') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('main.canceled') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }
}

if (!function_exists('formatRelation')) {
    function formatRelation($id, $related, string $attribute = 'name', ?string $routeName = null): string
    {
        if (!$id) {
            return '-';
        }

        if (!$related) {
            return '<span class="badge rounded-pill bg-label-danger">' . trans('teacher/errors.deleted') . '</span>';
        }

        $displayText = $related->$attribute ?? '-';

        if ($routeName) {
            $href = route($routeName, $id);
            return "<a target='_blank' href='{$href}'>{$displayText}</a>";
        }

        return $displayText;
    }
}

if (!function_exists('formatDuration')) {
    function formatDuration($duration): string
    {
        $minutes = $duration;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $hours . ' ' . trans('admin/zooms.hours') . '' .
            ($remainingMinutes > 0 ? ' ' . trans('admin/zooms.and') . ' ' .
            $remainingMinutes . ' ' . trans('admin/zooms.minute') . '' : '');
        }

        return $remainingMinutes . ' ' . trans('admin/zooms.minutes') . '';
    }
}

if (!function_exists('formatSpanUrl')) {
    function formatSpanUrl($href, $linkText, $color = 'success', $newTab = true): string
    {
        return
            '<a href="' . $href . '" ' .
            ($newTab ? 'target="_blank" ' : '') .
            'class="btn btn-sm btn-label-' . $color . ' waves-effect">' .
            $linkText .
            '</a>';
    }
}

if (!function_exists('getActiveGuard')) {
    function getActiveGuard()
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }
        return null;
    }
}

if (!function_exists('formatFrequency')) {
    function formatFrequency($frequency): string
    {
        switch ($frequency) {
            case 1:
                return '<span class="badge rounded-pill bg-label-primary text-capitalized">' . trans('main.one_time') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-info text-capitalized">' . trans('main.monthly') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('main.custom') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalized">-</span>';
        }
    }
}

if (!function_exists('formatInvoiceStatus')) {
    function formatInvoiceStatus($invoiceStatus): string
    {
        switch ($invoiceStatus) {
            case 1:
                return '<span class="badge rounded-pill bg-label-warning text-capitalized">' . trans('main.pending') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('main.paid') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-danger text-capitalized">' . trans('main.overdue') . '</span>';
            case 4:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalized">' . trans('main.canceled') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalized">-</span>';
        }
    }
}

if (!function_exists('generateInvoiceBalanceColumn')) {
    function generateInvoiceBalanceColumn($row)
    {
        $paid = $row['paid'];
        $due_date = $row['due_date'];
        $balance = number_format($row['amount'] - $paid, 2);
        $status = $row['status'];

        $statuses = [
            1 => [
                'label' => trans('main.pending'),
                'class' => 'bg-label-warning',
                'icon' => 'ri-time-line'
            ],
            2 => [
                'label' => trans('main.paid'),
                'class' => 'bg-label-success',
                'icon' => 'ri-check-line'
            ],
            3 => [
                'label' => trans('main.overdue'),
                'class' => 'bg-label-danger',
                'icon' => 'ri-error-warning-line'
            ],
            4 => [
                'label' => trans('main.canceled'),
                'class' => 'bg-label-secondary',
                'icon' => 'ri-close-line'
            ]
        ];

        $statusData = $statuses[$status];

        $tooltip = "<span>{$statusData['label']}<br><span class='fw-medium'>" . trans('main.due_amount') . ":</span> {$balance}<br><span class='fw-medium'>" . trans('main.due_date') . ":</span> {$due_date}";
        return '<span class="d-inline-block" data-bs-toggle="tooltip" data-bs-html="true" ' .
            'aria-label="' . $tooltip . '" ' .
            'data-bs-original-title="' . $tooltip . '">' .
            '<span class="badge rounded-pill ' . $statusData['class'] . ' px-2 py-1_5">' .
                '<i class="icon-base ri ' . $statusData['icon'] . ' icon-16px my-50"></i>' .
            '</span>' .
        '</span>';
    }
}

if (!function_exists('formatInvoiceReference')) {
    function formatInvoiceReference($idOrUuid, $route = null): string
    {
        if (Str::isUuid($idOrUuid)) {
            $number = substr($idOrUuid, 14, 4);
        } else {
            $number = $idOrUuid;
        }

        if ($route) {
            return '<a target="_blank" href="' . $route . '">#' . $number . '</a>';
        }

        return '<a href="#">#' . $number . '</a>';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $isLesson = false): string
    {
        $format = 'd M Y';

        if($isLesson)
        {
            $format = 'l d M Y';
        }

        return Carbon::parse($date, 'Africa/Cairo')->translatedFormat($format);
    }
}


if (!function_exists('getArabicOrdinal')) {
    function getArabicOrdinal($number, $isLastRank = false)
    {
        if (!is_numeric($number) || $number <= 0) {
            return trans('admin/quizzes.unranked');
        }

        if ($isLastRank) {
            return trans('admin/quizzes.lastRank');
        }

        $number = (int) $number;

        if ($number <= 10) {
            return trans("main.ordinals.{$number}");
        }

        $units = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $tens = ['', 'عشرة', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

        $result = '';

        if ($number >= 100) {
            $hundredCount = floor($number / 100);
            $number %= 100;
            $result .= $hundreds[$hundredCount];
        }

        if ($number > 0) {
            if ($result) {
                $result .= ' و';
            }
            if ($number >= 10) {
                $tenCount = floor($number / 10);
                $unitCount = $number % 10;
                if ($unitCount == 0) {
                    $result .= $tens[$tenCount];
                } else {
                    $result .= $units[$unitCount] . ' و' . $tens[$tenCount];
                }
            } else {
                $result .= $units[$number];
            }
        }

        return 'ال' . trim($result);
    }
}

if (!function_exists('getDashboardRoute')) {
    function getDashboardRoute() {
        return match (true) {
            isAdmin() => route('admin.dashboard'),
            isTeacher() => route('teacher.dashboard'),
            isAssistant() => route('assistant.dashboard'),
            isStudent() => route('student.dashboard'),
            isParent() => route('parent.dashboard'),
            default => route('login.choose'),
        };
    }
}

if (!function_exists('getHelpCenterRoute')) {
    function getHelpCenterRoute() {
        return match (true) {
            isAdmin() => route('admin.help-center.index'),
            isTeacher() => route('teacher.help-center.index'),
            isAssistant() => route('assistant.help-center.index'),
            isStudent() => route('student.help-center.index'),
            isParent() => route('parent.help-center.index'),
            default => route('login.choose'),
        };
    }
}
