<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    public function students(Request $request)
    {
        $transactionsQuery = Transaction::query()
            ->with(['student', 'invoice'])
            ->whereNull('teacher_id')
            ->select('id', 'type', 'student_id', 'invoice_id', 'amount', 'balance_after', 'description', 'payment_method', 'date', 'created_at');

        if ($request->ajax()) {
            return datatables()->eloquent($transactionsQuery)
            ->editColumn('invoice_id', function($row) {
                if (!$row->invoice_id) {
                    return 'N/A';
                }
                return formatInvoiceReference($row->invoice_id, route('admin.invoices.preview', $row->invoice_id));
            })
            ->editColumn('type', fn($row) => formatTransactionType($row->type))
            ->editColumn('student_id', fn($row) => formatRelation($row->student_id, $row->student, 'name', 'admin.students.profile.index'))
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

        return view('admin.finance.transactions.students');
    }

    public function teachers(Request $request)
    {
        $transactionsQuery = Transaction::query()
            ->with(['teacher', 'invoice'])
            ->whereNull('student_id')
            ->select('id', 'type', 'teacher_id', 'invoice_id', 'amount', 'balance_after', 'description', 'payment_method', 'date', 'created_at');

        if ($request->ajax()) {
            return datatables()->eloquent($transactionsQuery)
            ->editColumn('invoice_id', function($row) {
                if (!$row->invoice_id) {
                    return 'N/A';
                }
                return formatInvoiceReference($row->invoice_id, route('admin.invoices.preview', $row->invoice_id));
            })
            ->editColumn('type', fn($row) => formatTransactionType($row->type))
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name', 'admin.teachers.details'))
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->editColumn('balance_after', fn($row) => formatCurrency($row->balance_after) . ' ' . trans('main.currency'))
            ->editColumn('description', fn($row) => $row->description ?: '-')
            ->editColumn('payment_method', fn($row) => formatPaymentMethod($row->payment_method))
            ->editColumn('date', fn($row) => formatDate($row->date))
            ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
            ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
            ->rawColumns(['selectbox', 'invoice_id', 'type', 'teacher_id', 'amount', 'balance_after', 'payment_method', 'date'])
            ->make(true);
        }

        return view('admin.finance.transactions.teachers');
    }
}
