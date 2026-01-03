<?php

namespace App\Http\Controllers\Teacher\Finance;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Hash;
use App\Services\Teacher\Finance\InvoiceService;
use App\Http\Requests\Admin\Finance\InvoicesRequest;
use App\Http\Requests\Admin\Finance\PaymentsRequest;

class InvoicesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $invoiceService;
    protected $teacherId;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
        $selectedMonth = $request->get('month', now()->format('m'));

        $invoicesQuery = Invoice::query()
            ->fee()
            ->with(['student', 'fee'])
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->select('id', 'uuid', 'type', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'date', 'due_date', 'status')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId));

        if ($selectedMonth) {
            $month = sprintf('%02d', $selectedMonth);
                
            $invoicesQuery->whereHas('fee', function($q) use ($month) {
                $q->whereMonth('created_at', $month)
                ->where(function ($q) {
                    $q->whereYear('created_at', now()->year)
                        ->orWhereYear('created_at', now()->subYear()->year);
                    });            
                });        
            }

        if ($request->filled('status')) {
            if ($request->status == '3') { 
                $invoicesQuery->whereIn('status', [1, 3]);
            } else {
                $invoicesQuery->where('status', $request->status);
            }
        }

        $pageStatistics = [
            'clients' => Student::whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))->distinct('id')->count('id'),
            'invoices' => $invoicesQuery->count(),
        ];
        // 'paid' => $invoicesQuery->clone()->where('status', 2)->sum('amount'),
        // 'unpaid' => $invoicesQuery->clone()->whereIn('status', [1, 3])->sum('amount'),

        if ($request->ajax()) {
            return $this->invoiceService->getInvoicesForDatatable($invoicesQuery);
        }

        return view('teacher.finance.invoices.index', compact('pageStatistics'));
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:6',
        ]);

        if (Hash::check($request->pin, auth()->guard('teacher')->user()->financal_pin)) {
            $invoicesQuery = Invoice::query()
                ->fee()
                ->whereNull('teacher_id')
                ->whereNull('subscription_id')
                ->select('id', 'amount', 'status')
                ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
                ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId));

            return response()->json([
                'success' => true,
                'message' => trans('toasts.validPin'),
                'paid' => formatCurrency($invoicesQuery->clone()->where('status', 2)->sum('amount')) . ' ' . trans('main.currency'),
                'unpaid' => formatCurrency($invoicesQuery->clone()->whereIn('status', [1, 3])->sum('amount')) . ' ' . trans('main.currency'),
            ]);
        }

        return response()->json(['error' => trans('toasts.invalidPin')], 422);
    }

    public function preview($uuid)
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
            ->select('id', 'uuid', 'student_id', 'student_fee_id', 'fee_id', 'date', 'due_date', 'amount', 'status')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)->firstOrFail();

        $invoice->studentFee->totalAmount = $invoice->studentFee->amount ?? 'N/A';
        $invoice->studentFee->is_exempted = $invoice->studentFee->is_exempted ? trans('main.exempted') : trans('main.notexempted');
        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : 'N/A';

        if (!$invoice->studentFee) {
            abort(422, trans('toasts.noFeesFound'));
        }

        return view('teacher.finance.invoices.preview', compact('invoice'));
    }

    public function print($uuid)
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
            ->select('id', 'uuid', 'student_id', 'student_fee_id', 'fee_id', 'date', 'due_date', 'status')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)->firstOrFail();

        $invoice->studentFee->totalAmount = $invoice->studentFee->amount ?? 'N/A';
        $invoice->studentFee->is_exempted = $invoice->studentFee->is_exempted ? trans('main.exempted') : trans('main.notexempted');
        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : 'N/A';

        if (!$invoice->studentFee) {
            abort(422, trans('toasts.noFeesFound'));
        }

        return view('teacher.finance.invoices.print', compact('invoice'));
    }

    public function create()
    {
        $students = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        return view('teacher.finance.invoices.create', compact('students'));
    }

    public function insert(InvoicesRequest $request)
    {
        $result = $this->invoiceService->insertInvoice($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function edit($uuid)
    {
        $invoice = Invoice::with([
            'student:id,uuid,name,phone,email,grade_id,parent_id',
            'student.parent:id,name,phone,email',
            'student.grade:id,name',
            'studentFee:id,uuid,discount,is_exempted,fee_id',
            'fee:id,name,amount,teacher_id',
            'fee.teacher:id,name,phone,email',
            'transactions:id,invoice_id,amount,description,type',
        ])
            ->select('id', 'uuid', 'student_id', 'student_fee_id', 'fee_id', 'date', 'due_date', 'amount', 'status')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)->firstOrFail();

        $invoice->studentFee->totalAmount = $invoice->studentFee->amount ?? 'N/A';
        $invoice->studentFee->is_exempted = $invoice->studentFee->is_exempted ? trans('main.exempted') : trans('main.notexempted');
        $transaction = $invoice->transactions->firstWhere('type', 1);
        $invoice->description = $transaction && $transaction->description ? $transaction->description : null;

        $netPaid = $invoice->transactions->whereIn('type', [2, 3])->sum('amount');
        $balance = max(0, bcsub((string) $invoice->amount, (string) $netPaid, 2));
        $dueAmount = number_format($balance, 2);

        if (!$invoice->studentFee) {
            abort(422, trans('toasts.noFeesFound'));
        }

        $students = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        $studentFees = StudentFee::query()
            ->where('student_id', $invoice->student_id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->with('fee:id,name,grade_id,teacher_id', 'fee.grade:id,name')
            ->get()
            ->mapWithKeys(function ($studentFee) {
                $feeName = $studentFee->fee->name ?? 'N/A';
                $gradeName = $studentFee->fee->grade->name ?? 'N/A';
                return [$studentFee->uuid => sprintf('%s - %s', $feeName, $gradeName)];
            })
            ->toArray();

        return view('teacher.finance.invoices.edit', compact('invoice', 'students', 'studentFees', 'dueAmount'));
    }

    public function update(InvoicesRequest $request, $uuid)
    {
        $id = Invoice::uuid($uuid)->value('id');

        $result = $this->invoiceService->updateInvoice($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function payment(PaymentsRequest $request, $uuid)
    {
        $id = Invoice::uuid($uuid)->value('id');

        $result = $this->invoiceService->payInvoice($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function refund(PaymentsRequest $request, $uuid)
    {
        $id = Invoice::uuid($uuid)->value('id');

        $result = $this->invoiceService->refundInvoice($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = Invoice::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->deleteInvoice($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function cancel(Request $request)
    {
        $id = Invoice::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'invoices');

        $result = $this->invoiceService->cancelInvoice($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function transactions(Request $request, $uuid)
    {
        $invoice = Invoice::with(['student:id,name,grade_id', 'fee:id,name', 'student.grade:id,name'])
            ->fee()
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)
            ->firstOrFail();

        $transactionsQuery = Transaction::query()
            ->with(['student', 'invoice'])
            ->whereNull('teacher_id')
            ->select('id', 'type', 'student_id', 'invoice_id', 'amount', 'balance_after', 'description', 'payment_method', 'date', 'created_at')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('invoice', fn($query) => $query
                ->where('type', 2)
                ->whereNull('teacher_id')
                ->whereNull('subscription_id')
                ->whereHas('fee', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->where('invoice_id', $invoice->id)
            ->orderBy('id');

        if ($request->ajax()) {
            return datatables()->eloquent($transactionsQuery)
                ->editColumn('invoice_id', function ($row) {
                    if (!$row->invoice_id) {
                        return 'N/A';
                    }
                    return formatInvoiceReference($row->invoice->uuid, route('teacher.invoices.preview', $row->invoice->uuid));
                })
                ->editColumn('type', fn($row) => formatTransactionType($row->type))
                ->editColumn('student_id', fn($row) => formatRelation($row->student_id, $row->student, 'name', 'teacher.students.profile.index'))
                ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
                ->editColumn('balance_after', fn($row) => formatCurrency($row->balance_after) . ' ' . trans('main.currency'))
                ->editColumn('description', fn($row) => $row->description ?: '-')
                ->editColumn('payment_method', fn($row) => formatPaymentMethod($row->payment_method))
                ->editColumn('date', fn($row) => formatDate($row->date))
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
                ->rawColumns(['selectbox', 'invoice_id', 'type', 'student_id', 'amount', 'balance_after', 'payment_method', 'date'])
                ->make(true);
        }

        return view('teacher.finance.invoices.transactions', compact('invoice'));
    }
}
