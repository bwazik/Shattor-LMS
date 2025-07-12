<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Services\Admin\Finance\InvoiceService;
use App\Http\Requests\Admin\Finance\InvoicesRequest;
use App\Http\Requests\Admin\Finance\PaymentsRequest;
use App\Http\Requests\Admin\Finance\TeachersInvoicesRequest;
use App\Models\TeacherSubscription;

class TeachersInvoicesController extends Controller
{
    use ValidatesExistence;

    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $invoicesQuery = Invoice::query()
            ->subscription()
            ->with(['teacher', 'subscription', 'subscription.plan'])
            ->whereNull('student_id')
            ->whereNull('student_fee_id')
            ->whereNull('fee_id')
            ->select('id', 'type', 'teacher_id', 'subscription_id', 'amount', 'date', 'due_date', 'status');

        $pageStatistics = [
            'clients' => Teacher::distinct('id')->count('id'),
            'invoices' => $invoicesQuery->count(),
            'paid' => $invoicesQuery->clone()->where('status', 2)->sum('amount'),
            'unpaid' => $invoicesQuery->clone()->whereIn('status', [1, 3])->sum('amount'),
        ];

        if ($request->ajax()) {
            return $this->invoiceService->getTeachersInvoicesForDatatable($invoicesQuery);
        }

        return view('admin.finance.invoices.teachers.index', compact('pageStatistics'));
    }

    public function archived(Request $request)
    {
        $invoicesQuery = Invoice::query()
            ->subscription()
            ->with(['teacher', 'subscription', 'subscription.plan'])
            ->onlyTrashed()
            ->whereNull('student_id')
            ->whereNull('student_fee_id')
            ->whereNull('fee_id')
            ->select('id', 'type', 'teacher_id', 'subscription_id', 'amount', 'date', 'due_date', 'status');

        if ($request->ajax()) {
            return $this->invoiceService->getArchivedTeachersInvoicesForDatatable($invoicesQuery);
        }

        return view('admin.finance.invoices.teachers.archive');
    }

    public function preview($id)
    {
        $invoice = Invoice::with([
                'teacher:id,name,phone,email',
                'subscription:id,plan_id,period',
                'subscription.plan:id,name,monthly_price,term_price,year_price',
                'transactions:id,invoice_id,amount,description,type',
            ])
            ->select('id', 'teacher_id', 'subscription_id', 'date', 'due_date', 'amount', 'status')
            ->findOrFail($id);

        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : 'N/A';

        if (!$invoice->subscription) {
            abort(422, trans('toasts.noSubscriptionsFound'));
        }

        return view('admin.finance.invoices.teachers.preview', compact( 'invoice'));
    }

    public function print($id)
    {
        $invoice = Invoice::with([
            'teacher:id,name,phone,email',
            'subscription:id,plan_id,period',
            'subscription.plan:id,name,monthly_price,term_price,year_price',
            'transactions:id,invoice_id,amount,description,type',
        ])
        ->select('id', 'teacher_id', 'subscription_id', 'date', 'due_date', 'amount', 'status')
        ->findOrFail($id);

        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : 'N/A';

        if (!$invoice->subscription) {
            abort(422, trans('toasts.noSubscriptionsFound'));
        }

        return view('admin.finance.invoices.teachers.print', compact( 'invoice'));
    }

    public function create()
    {
        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.finance.invoices.teachers.create', compact('teachers'));
    }

    public function insert(TeachersInvoicesRequest $request)
    {
        $result = $this->invoiceService->insertTeacherInvoice($request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function edit($id)
    {
        $invoice = Invoice::with([
            'teacher:id,name,phone,email',
            'subscription:id,plan_id,period',
            'subscription.plan:id,name,monthly_price,term_price,year_price',
            'transactions:id,invoice_id,amount,description,type',
            ])
            ->select('id', 'teacher_id', 'subscription_id', 'date', 'due_date', 'amount', 'status')
            // ->whereIn('status', [1, 2, 3])
            ->findOrFail($id);

        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : null;

        $netPaid = $invoice->transactions->whereIn('type', [2, 3])->sum('amount');
        $balance = max(0, bcsub((string)$invoice->amount, (string)$netPaid, 2));
        $dueAmount = number_format($balance, 2);

        if (!$invoice->subscription) {
            abort(422, trans('toasts.noSubscriptionsFound'));
        }

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $teacherSubscriptions = TeacherSubscription::query()
            ->where('teacher_id', $invoice->teacher_id)
            ->with(['plan'])
            ->get()
            ->mapWithKeys(function ($teacherSubscription) {
                $planName = $teacherSubscription->plan->name ?? 'N/A';
                return [$teacherSubscription->id => $planName];
            })->toArray();

        return view('admin.finance.invoices.teachers.edit', compact( 'invoice', 'teachers', 'teacherSubscriptions', 'dueAmount'));
    }

    public function update(TeachersInvoicesRequest $request, $id)
    {
        $result = $this->invoiceService->updateTeacherInvoice($id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function payment(PaymentsRequest $request, $id)
    {
        $result = $this->invoiceService->payTeacherInvoice($id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function refund(PaymentsRequest $request, $id)
    {
        $result = $this->invoiceService->refundTeacherInvoice($id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->deleteTeacherInvoice($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function cancel(Request $request)
    {
        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->cancelTeacherInvoice($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function archive(Request $request)
    {
        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->archiveInvoice($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function restore(Request $request)
    {
        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->restoreInvoice($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }
}
