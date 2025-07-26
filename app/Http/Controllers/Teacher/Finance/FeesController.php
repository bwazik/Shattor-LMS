<?php

namespace App\Http\Controllers\Teacher\Finance;

use Carbon\Carbon;
use App\Models\Fee;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Teacher\Finance\FeeService;
use App\Http\Requests\Admin\Finance\FeesRequest;

class FeesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $feeService;
    protected $teacherId;

    public function __construct(FeeService $feeService)
    {
        $this->feeService = $feeService;
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
        $feesQuery = Fee::query()->with(['grade:id,name'])
            ->select('id', 'uuid', 'name', 'amount', 'grade_id', 'frequency')
            ->where('teacher_id', $this->teacherId);

        if ($request->ajax()) {
            return $this->feeService->getFeesForDatatable($feesQuery);
        }

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        return view('teacher.finance.fees.index', compact('grades'));
    }


    public function insert(FeesRequest $request)
    {
        $result = $this->feeService->insertFee($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(FeesRequest $request)
    {
        $id = Fee::uuid($request->id)->value('id');

        $result = $this->feeService->updateFee($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = Fee::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'fees');

        $result = $this->feeService->deleteFee($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $ids = Fee::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'fees');

        $result = $this->feeService->deleteSelectedFees($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function reports($uuid)
    {
        $fee = Fee::uuid($uuid)
            ->with(['grade:id,name'])
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $invoicesQuery = Invoice::query()
            ->fee()
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->select('id', 'uuid', 'type', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'date', 'due_date', 'status');

        // Total students eligible for the fee
        $totalStudents = Student::where('grade_id', $fee->grade_id)
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->distinct('id')
            ->count('id');

        // Total students with invoices
        $totalStudentsWithInvoices = $invoicesQuery->clone()->distinct('student_id')->count('student_id');

        // Student counts for paid and unpaid invoices
        $paidStudents = $invoicesQuery->clone()->where('status', 2)->distinct('student_id')->count('student_id');
        $unpaidStudents = $invoicesQuery->clone()->whereIn('status', [1, 3])->distinct('student_id')->count('student_id');

        // Fetch statistics
        $pageStatistics = [
            'totalStudents' => $totalStudents,
            'totalStudentsWithInvoices' => $totalStudentsWithInvoices,
            'invoices' => $invoicesQuery->count(),
            'paid' => $invoicesQuery->clone()->where('status', 2)->sum('amount'),
            'unpaid' => $invoicesQuery->clone()->whereIn('status', [1, 3])->sum('amount'),
            'paidFee' => $paidStudents,
            'didntPayFee' => $unpaidStudents,
            'paidFeePercentage' => $totalStudentsWithInvoices > 0 ? round(($paidStudents / $totalStudentsWithInvoices) * 100, 1) : 0,
            'didntPayFeePercentage' => $totalStudentsWithInvoices > 0 ? round(($unpaidStudents / $totalStudentsWithInvoices) * 100, 1) : 0,
        ];

        // Payment trends
        $startDate = Carbon::parse($fee->created_at, 'Africa/Cairo')->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();
        $dateRange = collect(Carbon::parse($startDate)->daysUntil($endDate)->toArray())->map(fn($date) => $date->format('Y-m-d'));

        $paymentTrendsQuery = Invoice::query()
            ->where('invoices.type', 2)
            ->whereNull('invoices.teacher_id')
            ->whereNull('invoices.subscription_id')
            ->where('invoices.fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('transactions', fn($query) => $query->where('transactions.type', 2))
            ->whereBetween('transactions.date', [$dateRange->first(), $dateRange->last() . ' 23:59:59'])
            ->selectRaw('DATE(transactions.date) as date, COUNT(DISTINCT invoices.student_id) as count')
            ->join('transactions', 'invoices.id', '=', 'transactions.invoice_id')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $paymentTrends = $dateRange->mapWithKeys(function ($date) use ($paymentTrendsQuery) {
            return [$date => $paymentTrendsQuery[$date] ?? 0];
        })->values()->toArray();

        $paymentDates = $dateRange->map(fn($date) => Carbon::parse($date)->translatedFormat('d F', app()->getLocale()))->toArray();

        // Add to reports method
        $paymentMethodsQuery = Invoice::query()
            ->where('invoices.type', 2)
            ->whereNull('invoices.teacher_id')
            ->whereNull('invoices.subscription_id')
            ->where('invoices.fee_id', $fee->id)
            ->where('status', 2)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('transactions', fn($query) => $query->where('transactions.type', 2))
            ->join('transactions', 'invoices.id', '=', 'transactions.invoice_id')
            ->groupBy('transactions.payment_method')
            ->selectRaw('transactions.payment_method, COUNT(*) as count, SUM(transactions.amount) as total_amount')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method => ['count' => $item->count, 'amount' => $item->total_amount]];
            })
            ->toArray();

        $paymentMethods = [
            'cash' => ['count' => $paymentMethodsQuery[1]['count'] ?? 0, 'amount' => $paymentMethodsQuery[1]['amount'] ?? 0],
            'vodafone_cash' => ['count' => $paymentMethodsQuery[2]['count'] ?? 0, 'amount' => $paymentMethodsQuery[2]['amount'] ?? 0],
            'instapay' => ['count' => $paymentMethodsQuery[3]['count'] ?? 0, 'amount' => $paymentMethodsQuery[3]['amount'] ?? 0],
            'balance' => ['count' => $paymentMethodsQuery[4]['count'] ?? 0, 'amount' => $paymentMethodsQuery[4]['amount'] ?? 0],
        ];

        // Data for the chart
        $data = [
            'paymentTrends' => $paymentTrends,
            'paymentDates' => $paymentDates,
            'paymentMethods' => $paymentMethods,
        ];

        return view('teacher.finance.fees.reports', compact('fee', 'pageStatistics', 'data'));
    }

    public function studentsPaidFee(Request $request, $uuid)
    {
        $fee = Fee::uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $invoicesQuery = Invoice::query()
            ->fee()
            ->where('status', 2)
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->with(['student', 'transactions' => fn($query) => $query->where('type', 2)]);

        if ($request->ajax()) {
            return datatables()->eloquent($invoicesQuery)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->phone, 'teacher.students.profile.index', $row->student->uuid))
                ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
                ->editColumn('date', fn($row) => formatDate($row->date))
                ->addColumn('paymentDate', fn($row) => $row->transactions->isNotEmpty() ? isoFormat($row->transactions->max('created_at')) : 'N/A')
                ->editColumn('payment_method', fn($row) => $row->transactions->isNotEmpty() ? formatPaymentMethod($row->transactions->max('payment_method')) : 'N/A')
                ->addColumn('transactions', fn($row) => formatSpanUrl(route('teacher.invoices.transactions', $row->uuid), trans('admin/transactions.transactions')))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details', 'payment_method', 'transactions'])
                ->make(true);
        }
    }

    public function studentsHavenotPaidFee(Request $request, $uuid)
    {
        $fee = Fee::uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $invoicesQuery = Invoice::query()
            ->fee()
            ->whereIn('status', [1, 3])
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->with(['student']);

        if ($request->ajax()) {
            return datatables()->eloquent($invoicesQuery)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->phone, 'teacher.students.profile.index', $row->student->uuid))
                ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
                ->editColumn('date', fn($row) => formatDate($row->date))
                ->editColumn('status', fn($row) => formatInvoiceStatus($row->status))
                ->addColumn('transactions', fn($row) => formatSpanUrl(route('teacher.invoices.transactions', $row->uuid), trans('admin/transactions.transactions')))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->filterColumn('status', fn($query, $keyword) => filterByInvoiceStatus($query, $keyword))
                ->rawColumns(['details', 'status', 'transactions'])
                ->make(true);
        }
    }

    public function studentsWithoutFee(Request $request, $uuid)
    {
        $fee = Fee::uuid($uuid)
            ->with(['grade'])
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $studentsQuery = Student::query()
            ->where('grade_id', $fee->grade_id)
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->whereDoesntHave('invoices', fn($query) => $query->where('fee_id', $fee->id))
            ->select('id', 'uuid', 'name', 'email', 'grade_id', 'profile_pic', 'created_at');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsQuery)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details'])
                ->make(true);
        }
    }
}
