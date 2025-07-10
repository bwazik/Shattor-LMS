<?php

namespace App\Services\Teacher\Account;

use App\Models\Wallet;
use App\Models\Invoice;
use App\Models\Teacher;
use App\Models\Transaction;
use App\Models\TeacherSubscription;
use Illuminate\Support\Facades\URL;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class BillingService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getInvoicesForDatatable($invoicesQuery)
    {
        return datatables()->eloquent($invoicesQuery)
            ->editColumn('uuid', fn($row) => formatInvoiceReference($row->uuid, route('teacher.billing.invoices.preview', $row->uuid)))
            ->addColumn('balance', fn($row) => generateInvoiceBalanceColumn([
                'paid' => Transaction::where('invoice_id', $row->id)->where('type', 2)->sum('amount'),
                'due_date' => formatDate($row->due_date),
                'amount' => $row->amount,
                'status' => $row->status
            ]))
            ->editColumn('subscription_id', fn($row) => $row->subscription->plan->id ? $row->subscription->plan->name : '-')
            ->editColumn('date', fn($row) => formatDate($row->date))
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->editColumn('status', fn($row) => formatInvoiceStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateInvoicesActionButtons($row))
            ->filterColumn('status', fn($query, $keyword) => filterByInvoiceStatus($query, $keyword))
            ->rawColumns(['uuid', 'balance', 'status', 'actions'])
            ->make(true);
    }

    private function generateInvoicesActionButtons($row): string
    {
        return
            '<a target="_blank" href="' . route('teacher.billing.invoices.preview', $row->uuid) . '" class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                'data-bs-toggle="tooltip" aria-label="' . trans('main.previewItem', ['item' => trans('admin/invoices.theInvoice')]) . '" data-bs-original-title="' . trans('main.previewItem', ['item' => trans('admin/invoices.theInvoice')]) . '">' .
                '<i class="ri-eye-line ri-20px"></i>' .
            '</a>' .
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    ($row->status === 1 || $row->status === 3 ?
                    '<li>
                        <a target="_blank" href="' . $this->generateSignedPayUrl($row->uuid, 'get') . '" class="dropdown-item">'.trans('main.addItem', ['item' => trans('main.payment')]).'</a>
                    </li>' : '') .
                    '<li>
                        <a target="_blank" href="' . route('teacher.billing.invoices.print', $row->uuid) . '" class="dropdown-item">'.trans('main.print').'</a>
                    </li>' .
                '</ul>' .
            '</div>';
    }

    public function processPayment($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $invoice = Invoice::with([
                'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
            ])
                ->where('teacher_id', $this->teacherId)
                ->whereIn('status', [1, 3])
                ->findOrFail($id);

            if ($validationResult = $this->validateTeacherPaymentData($id, $request['amount']))
                return $validationResult;

            if ((int)$request['payment_method'] !== 4) {
                return $this->errorResponse(trans('toasts.paymentMethodNotAllowed'));
            }

            $netPaid = $invoice->transactions->sum('amount');
            $remaining = bcsub((string)$invoice->amount, (string)$netPaid, 2);

            if ($remaining <= 0) {
                $invoice->update(['status' => 2]);
                return $this->successResponse(trans('main.added', ['item' => trans('main.payment')]));
            }

            $balanceAfter = null;
            if ($request['payment_method'] == 4) {
                $teacher = Teacher::where('id', $this->teacherId)->lockForUpdate()->firstOrFail();
                if ($teacher->balance < $request['amount']) {
                    return $this->errorResponse(trans('toasts.insufficientBalance'));
                }
                $balanceAfter = bcsub((string)$teacher->balance, (string)$request['amount'], 2);
                $teacher->update(['balance' => $balanceAfter]);
            }

            Transaction::create([
                'type' => 2,
                'teacher_id' => $this->teacherId,
                'invoice_id' => $invoice->id,
                'amount' => bcadd((string)$request['amount'], '0', 2),
                'balance_after' => bcadd((string)$this->getFounderWalletBalance(), (string)$request['amount'], 2),
                'description' => $request['description'] ?? null,
                'payment_method' => $request['payment_method'],
                'date' => now()->format('Y-m-d'),
            ]);

            $wallet = Wallet::firstOrCreate(
                ['user_id' => 1],
                ['balance' => 0.00]
            )->lockForUpdate();
            $wallet->increment('balance', $request['amount']);

            $netPaid = bcadd((string)$netPaid, (string)$request['amount'], 2);
            if ($netPaid >= $invoice->amount) {
                $invoice->update(['status' => 2]);
            }

            return $this->successResponse(trans('main.added', ['item' => trans('main.payment')]));
        }, trans('toasts.invoiceNotPayable'));
    }

    public function getTransactionsForDatatable($transactionsQuery)
    {
        return datatables()->eloquent($transactionsQuery)
            ->editColumn('invoice_id', function($row) {
                if (!$row->invoice_id) {
                    return 'N/A';
                }
                return formatInvoiceReference($row->invoice->uuid, route('teacher.billing.invoices.preview', $row->invoice->uuid));
            })
            ->editColumn('type', fn($row) => formatTransactionType($row->type))
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->editColumn('description', fn($row) => $row->description ?: '-')
            ->editColumn('payment_method', fn($row) => formatPaymentMethod($row->payment_method))
            ->editColumn('date', fn($row) => formatDate($row->date))
            ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
            ->rawColumns(['selectbox', 'invoice_id', 'type', 'amount', 'payment_method', 'date'])
            ->make(true);
    }
}
