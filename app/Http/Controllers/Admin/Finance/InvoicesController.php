<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Services\Admin\Finance\InvoiceService;
use App\Http\Requests\Admin\Finance\InvoicesRequest;
use App\Http\Requests\Admin\Finance\PaymentsRequest;

class InvoicesController extends Controller
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
            ->fee()
            ->with(['student', 'fee'])
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->select('id', 'type', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'date', 'due_date', 'status');

        $pageStatistics = [
            'clients' => Student::distinct('id')->count('id'),
            'invoices' => $invoicesQuery->count(),
            'paid' => $invoicesQuery->clone()->where('status', 2)->sum('amount'),
            'unpaid' => $invoicesQuery->clone()->whereIn('status', [1, 3])->sum('amount'),
        ];

        if ($request->ajax()) {
            return $this->invoiceService->getInvoicesForDatatable($invoicesQuery);
        }

        return view('admin.finance.invoices.index', compact('pageStatistics'));
    }

    public function archived(Request $request)
    {
        $invoicesQuery = Invoice::query()
            ->with(['student', 'fee'])
            ->fee()
            ->onlyTrashed()
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->select('id', 'type', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'date', 'due_date', 'status');

        if ($request->ajax()) {
            return $this->invoiceService->getArchivedInvoicesForDatatable($invoicesQuery);
        }

        return view('admin.finance.invoices.archive');
    }

    public function preview($id)
    {
        $invoice = Invoice::with([
                'student:id,name,phone,email,grade_id,parent_id',
                'student.parent:id,name,phone,email',
                'student.grade:id,name',
                'studentFee:id,discount,is_exempted,fee_id',
                'fee:id,amount,teacher_id',
                'fee.teacher:id,name,phone,email',
                'transactions:id,invoice_id,amount,description,type',
            ])
            ->select('id', 'student_id', 'student_fee_id', 'fee_id', 'date', 'due_date', 'amount', 'status')
            ->findOrFail($id);

        $invoice->studentFee->totalAmount = $invoice->studentFee->amount ?? 'N/A';
        $invoice->studentFee->is_exempted = $invoice->studentFee->is_exempted ? trans('main.exempted') : trans('main.notexempted');
        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : 'N/A';

        if (!$invoice->studentFee) {
            abort(422, trans('toasts.noFeesFound'));
        }

        return view('admin.finance.invoices.preview', compact( 'invoice'));
    }

    public function print($id)
    {
        $invoice = Invoice::with([
                'student:id,name,phone,email,grade_id,parent_id',
                'student.parent:id,name,phone,email',
                'student.grade:id,name',
                'studentFee:id,discount,is_exempted,fee_id',
                'fee:id,amount,teacher_id',
                'fee.teacher:id,name,phone,email',
                'transactions:id,invoice_id,amount,description,type',
            ])
            ->select('id', 'student_id', 'student_fee_id', 'fee_id', 'date', 'due_date', 'status')
            ->findOrFail($id);

        $invoice->studentFee->totalAmount = $invoice->studentFee->amount ?? 'N/A';
        $invoice->studentFee->is_exempted = $invoice->studentFee->is_exempted ? trans('main.exempted') : trans('main.notexempted');
        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : 'N/A';

        if (!$invoice->studentFee) {
            abort(422, trans('toasts.noFeesFound'));
        }

        return view('admin.finance.invoices.print', compact( 'invoice'));
    }

    public function create()
    {
        $students = Student::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.finance.invoices.create', compact('students'));
    }

    public function insert(InvoicesRequest $request)
    {
        $result = $this->invoiceService->insertInvoice($request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function edit($id)
    {
        $invoice = Invoice::with([
                'student:id,name,phone,email,grade_id,parent_id',
                'student.parent:id,name,phone,email',
                'student.grade:id,name',
                'studentFee:id,discount,is_exempted,fee_id',
                'fee:id,name,amount,teacher_id',
                'fee.teacher:id,name,phone,email',
                'transactions:id,invoice_id,amount,description,type',
            ])
            ->select('id', 'student_id', 'student_fee_id', 'fee_id', 'date', 'due_date', 'amount', 'status')
            // ->whereIn('status', [1, 2, 3])
            ->findOrFail($id);

        $invoice->studentFee->totalAmount = $invoice->studentFee->amount ?? 'N/A';
        $invoice->studentFee->is_exempted = $invoice->studentFee->is_exempted ? trans('main.exempted') : trans('main.notexempted');
        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : null;

        $netPaid = $invoice->transactions->whereIn('type', [2, 3])->sum('amount');
        $balance = max(0, bcsub((string)$invoice->amount, (string)$netPaid, 2));
        $dueAmount = number_format($balance, 2);

        if (!$invoice->studentFee) {
            abort(422, trans('toasts.noFeesFound'));
        }

        $students = Student::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $studentFees = StudentFee::query()
            ->where('student_id', $invoice->student_id)
            ->with('fee:id,name,grade_id,teacher_id', 'fee.grade:id,name', 'fee.teacher:id,name')
            ->get()
            ->mapWithKeys(function ($studentFee) {
                $feeName = $studentFee->fee->name ?? 'N/A';
                $gradeName = $studentFee->fee->grade->name ?? 'N/A';
                $teacherName = $studentFee->fee->teacher->name ?? 'N/A';
                return [$studentFee->id => sprintf('%s - %s - %s', $feeName, $teacherName, $gradeName)];
            })
            ->toArray();

        return view('admin.finance.invoices.edit', compact( 'invoice', 'students', 'studentFees', 'dueAmount'));
    }

    public function update(InvoicesRequest $request, $id)
    {
        $result = $this->invoiceService->updateInvoice($id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function payment(PaymentsRequest $request, $id)
    {
        $result = $this->invoiceService->payInvoice($id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function refund(PaymentsRequest $request, $id)
    {
        $result = $this->invoiceService->refundInvoice($id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->deleteInvoice($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function cancel(Request $request)
    {
        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->cancelInvoice($request->id);

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
