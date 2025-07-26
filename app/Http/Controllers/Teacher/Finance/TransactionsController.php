<?php

namespace App\Http\Controllers\Teacher\Finance;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
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
            ->orderBy('id');

        if ($request->ajax()) {
            return datatables()->eloquent($transactionsQuery)
            ->editColumn('invoice_id', function($row) {
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

        return view('teacher.finance.transactions.index');
    }
}
