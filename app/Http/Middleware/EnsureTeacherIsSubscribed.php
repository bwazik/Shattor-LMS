<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\TeacherSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherIsSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('teacher')->check()) {
            return Redirect::route('login');
        }

        $teacher = Auth::guard('teacher')->user();
        $allowedRoutes = [
            // Plans
            'teacher.plans.index',
            'teacher.fetch.plans.data',

            // Billing & Subscriptions
            'teacher.billing.index',
            'teacher.billing.transactions',
            'teacher.billing.invoices',
            'teacher.billing.invoices.print',
            'teacher.billing.invoices.preview',
            'teacher.billing.invoices.pay',
            'teacher.billing.invoices.process',
            'teacher.subscriptions.index',
            'teacher.subscriptions.insert',
            'teacher.subscriptions.cancle',

            // Account Management
            'teacher.account.personal.edit',
            'teacher.account.updateProfilePic',
            'teacher.account.personal.update',
            'teacher.account.security.index',
            'teacher.account.password.update',
            'teacher.account.coupons.index',
            'teacher.account.coupons.redeem',

            // Misc
            'teacher.faqs.index',
            'teacher.help-center.index',
            'teacher.help-center.show',
        ];

        if (in_array($request->route()->getName(), $allowedRoutes)) {
            return $next($request);
        }

        if (is_null($teacher->plan_id)) {
            return Redirect::route('teacher.plans.index');
        }

        $activeSubscription = TeacherSubscription::where('teacher_id', $teacher->id)
            ->where('status', 1)
            ->where('end_date', '>=', now())
            ->first();

        if ($activeSubscription) {
            $paidInvoice = Invoice::where('subscription_id', $activeSubscription->id)
                ->subscription()
                ->paid()
                ->exists();

            if ($paidInvoice) {
                return $next($request);
            }
        } else {
            return Redirect::route('teacher.plans.index');
        }

        return $next($request);
    }
}
