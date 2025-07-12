<?php

namespace App\Http\Controllers\Admin\Finance;

use Carbon\Carbon;
use App\Models\Fee;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Admin\Finance\FeeService;
use App\Http\Requests\Admin\Finance\FeesRequest;

class FeesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $feeService;

    public function __construct(FeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    public function index(Request $request)
    {
        $feesQuery = Fee::query()->with(['teacher', 'grade'])->select('id', 'name', 'amount', 'teacher_id', 'grade_id', 'frequency');

        if ($request->ajax()) {
            return $this->feeService->getFeesForDatatable($feesQuery);
        }

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $grades = Grade::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.finance.fees.index', compact('teachers', 'grades'));
    }


    public function insert(FeesRequest $request)
    {
        $result = $this->feeService->insertFee($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(FeesRequest $request)
    {
        $result = $this->feeService->updateFee($request->id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'fees');

        $result = $this->feeService->deleteFee($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'fees');

        $result = $this->feeService->deleteSelectedFees($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function reports($id)
    {
        $fee = Fee::with(['grade:id,name', 'teacher:id,name'])->findOrFail($id);

        $invoicesQuery = Invoice::query()
            ->fee()
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $fee->teacher_id)))
            ->select('id', 'type', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'date', 'due_date', 'status');

        // Total students eligible for the fee
        $totalStudents = Student::where('grade_id', $fee->grade_id)
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $fee->teacher_id))
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
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $fee->teacher_id)))
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

        // Data for the chart
        $data = [
            'paymentTrends' => $paymentTrends,
            'paymentDates' => $paymentDates,
        ];

        return view('admin.finance.fees.reports', compact('fee', 'pageStatistics', 'data'));
    }

    public function studentsPaidFee(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);

        $invoicesQuery = Invoice::query()
            ->fee()
            ->where('status', 2)
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $fee->teacher_id)))
            ->with(['student', 'transactions' => fn($query) => $query->where('type', 2)]);

        if ($request->ajax()) {
            return datatables()->eloquent($invoicesQuery)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->email, 'admin.students.details', $row->student->id))
                ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
                ->editColumn('date', fn($row) => formatDate($row->date))
                ->addColumn('paymentDate', fn($row) => $row->transactions->isNotEmpty() ? isoFormat($row->transactions->max('created_at')) : 'N/A')
                ->addColumn('transactions', fn($row) => formatSpanUrl(route('admin.invoices.transactions', $row->id), trans('admin/transactions.transactions')))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
                ->rawColumns(['details', 'transactions'])
                ->make(true);
        }
    }

    public function studentsHavenotPaidFee(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);

        $invoicesQuery = Invoice::query()
            ->fee()
            ->whereIn('status', [1, 3])
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('fee_id', $fee->id)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $fee->teacher_id)))
            ->with(['student']);

        if ($request->ajax()) {
            return datatables()->eloquent($invoicesQuery)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->email, 'admin.students.details', $row->student->id))
                ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
                ->editColumn('date', fn($row) => formatDate($row->date))
                ->editColumn('status', fn($row) => formatInvoiceStatus($row->status))
                ->addColumn('transactions', fn($row) => formatSpanUrl(route('admin.invoices.transactions', $row->id), trans('admin/transactions.transactions')))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
                ->filterColumn('status', fn($query, $keyword) => filterByInvoiceStatus($query, $keyword))
                ->rawColumns(['details', 'status', 'transactions'])
                ->make(true);
        }
    }

    public function studentsWithoutFee(Request $request, $id)
    {
        $fee = Fee::with(['grade'])->findOrFail($id);

        $studentsQuery = Student::query()
            ->where('grade_id', $fee->grade_id)
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $fee->teacher_id))
            ->whereDoesntHave('invoices', fn($query) => $query->where('fee_id', $fee->id))
            ->select('id', 'name', 'email', 'grade_id', 'profile_pic');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsQuery)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->email, 'admin.students.details', $row->id))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
                ->rawColumns(['details'])
                ->make(true);
        }
    }
}
