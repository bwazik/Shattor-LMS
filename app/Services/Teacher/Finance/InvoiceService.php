<?php

namespace App\Services\Teacher\Finance;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Transaction;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class InvoiceService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];
    protected $transModelKey = 'admin/invoices.invoices';
    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getInvoicesForDatatable($invoicesQuery)
    {
        return datatables()->eloquent($invoicesQuery)
            ->editColumn('uuid', fn($row) => formatInvoiceReference($row->uuid, route('teacher.invoices.preview', $row->uuid)))
            ->addColumn('balance', fn($row) => generateInvoiceBalanceColumn([
                'paid' => Transaction::where('invoice_id', $row->id)->where('type', 2)->sum('amount'),
                'due_date' => formatDate($row->due_date),
                'amount' => $row->amount,
                'status' => $row->status
            ]))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->email, 'admin.students.details', $row->student->uuid))
            ->editColumn('fee_id', fn($row) => $row->fee_id ? $row->fee->name : '-')
            ->editColumn('date', fn($row) => formatDate($row->date))
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->editColumn('status', fn($row) => formatInvoiceStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
            ->filterColumn('fee_id', fn($query, $keyword) => filterByRelation($query, 'fee', 'name', $keyword))
            ->filterColumn('status', fn($query, $keyword) => filterByInvoiceStatus($query, $keyword))
            ->rawColumns(['uuid', 'balance', 'details', 'status', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row): string
    {
        $netPaid = $row->transactions->whereIn('type', [2, 3])->sum('amount');
        $balance = max(0, bcsub((string)$row->amount, (string)$netPaid, 2));
        $dueAmount = number_format($balance, 2);

        return
            '<a target="_blank" href="' . route('teacher.invoices.preview', $row->uuid) . '" class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                'data-bs-toggle="tooltip" aria-label="' . trans('main.previewItem', ['item' => trans('admin/invoices.theInvoice')]) . '" data-bs-original-title="' . trans('main.previewItem', ['item' => trans('admin/invoices.theInvoice')]) . '">' .
                '<i class="ri-eye-line ri-20px"></i>' .
            '</a>' .
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>' .
                        '<button type="button" class="dropdown-item" ' .
                            'tabindex="0" data-bs-toggle="offcanvas" data-bs-target="#payment-modal" ' .
                            'id="payment-button" ' .
                            'data-id="' . $row->uuid . '" ' .
                            'data-due_amount="' . $dueAmount . '">' .
                            trans('main.addItem', ['item' => trans('main.payment')]).
                        '</button>' .
                    '</li>' .
                    '<li>' .
                        '<button type="button" class="dropdown-item" ' .
                            'tabindex="0" data-bs-toggle="offcanvas" data-bs-target="#refund-modal" ' .
                            'id="refund-button" ' .
                            'data-id="' . $row->uuid . '" ' .
                            'data-due_amount="' . $dueAmount . '">' .
                            trans('main.addItem', ['item' => trans('main.refund')]).
                        '</button>' .
                    '</li>' .
                    '<li>
                        <a target="_blank" href="' . route('teacher.invoices.transactions', $row->uuid) . '" class="dropdown-item">'.trans('admin/transactions.transactions').'</a>
                    </li>' .
                    '<li>
                        <a target="_blank" href="' . route('teacher.invoices.edit', $row->uuid) . '" class="dropdown-item">'.trans('main.edit').'</a>
                    </li>' .
                    '<li>
                        <a target="_blank" href="' . route('teacher.invoices.print', $row->uuid) . '" class="dropdown-item">'.trans('main.print').'</a>
                    </li>' .
                    '<div class="dropdown-divider"></div>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="delete-button" ' .
                            'data-id="' . $row->uuid . '" ' .
                            'data-fee="' . $row->fee->name . '" ' .
                            'data-student="' . $row->student->name . '" ' .
                            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.delete').
                        '</a>' .
                    '</li>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="cancel-button" ' .
                            'data-id="' . $row->uuid . '" ' .
                            'data-fee="' . $row->fee->name . '" ' .
                            'data-student="' . $row->student->name . '" ' .
                            'data-bs-target="#cancel-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.cancel').
                        '</a>' .
                    '</li>' .
                '</ul>' .
            '</div>';
    }

    public function insertInvoice(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $studentId = Student::uuid($request['student_id'])->firstOrFail('id')->id;
            $studentFee = StudentFee::uuid($request['student_fee_id'])->firstOrFail();

            if ($validationResult = $this->validateStudentFeeForInvoice($studentFee->id, $studentId))
                return $validationResult;

            $invoice = Invoice::create([
                'type' => 2,
                'student_id' => $studentId,
                'student_fee_id' => $studentFee->id,
                'fee_id' => $studentFee->fee_id,
                'amount' => $studentFee->amount,
                'date' => $request['date'],
                'due_date' => $request['due_date'],
                'status' => 1,
            ]);

            Transaction::create([
                'type' => 1,
                'student_id' => $studentId,
                'invoice_id' => $invoice->id,
                'amount' => $studentFee->amount,
                'balance_after' => $this->getTeacherWalletBalance($invoice->fee->teacher_id),
                'description' => $request['description'] ?? null,
                'date' => now()->format('Y-m-d'),
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/invoices.invoice')]));
        });
    }

    public function updateInvoice($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            if (!Invoice::where('id', $id)->whereIn('status', [1, 3])->exists()) {
                return $this->errorResponse(trans('toasts.invoiceNotEditable'));
            }

            $studentId = Student::uuid($request['student_id'])->firstOrFail('id')->id;
            $studentFee = StudentFee::uuid($request['student_fee_id'])->firstOrFail();

            if ($validationResult = $this->validateStudentFeeForInvoice($studentFee->id, $studentId, $id))
                return $validationResult;

            $invoice = Invoice::findOrFail($id);
            $transaction = Transaction::where('invoice_id', $invoice->id)->invoice()->first();

            $today = now()->startOfDay()->toDateString();
            $newDueDate = $request['due_date'];
            $status = $invoice->status;

            if ($invoice->status === 3 && $newDueDate > $today) {
                $status = 1;
            } elseif ($invoice->status === 1 && $newDueDate < $today) {
                $status = 3;
            }

            $invoice->update([
                'student_id' => $studentId,
                'student_fee_id' => $studentFee->id,
                'fee_id' => $studentFee->fee_id,
                'amount' => $studentFee->amount,
                'date' => $request['date'],
                'due_date' => $request['due_date'],
                'status' => $status,
            ]);

            if ($transaction) {
                $transaction->update([
                    'student_id' => $studentId,
                    'amount' => $studentFee->amount,
                    'description' => $request['description'] ?? null,
                ]);
            }

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/invoices.invoice')]));
        });
    }

    public function payInvoice($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $invoice = Invoice::with([
                'studentFee',
                'fee:id,teacher_id',
                'fee.teacher:id',
                'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
            ])
                ->whereIn('status', [1, 3])
                ->findOrFail($id);

            if ($validationResult = $this->validatePaymentData($id, $request['amount']))
                return $validationResult;

            $netPaid = $invoice->transactions->sum('amount');
            $remaining = bcsub((string)$invoice->amount, (string)$netPaid, 2);

            if ($remaining <= 0 || $invoice->studentFee->is_exempted) {
                $invoice->update(['status' => 2]);
                return $this->successResponse(trans('main.added', ['item' => trans('main.payment')]));
            }

            Transaction::create([
                'type' => 2,
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'amount' => bcadd((string)$request['amount'], '0', 2),
                'balance_after' => bcadd((string)$this->getTeacherWalletBalance($invoice->fee->teacher_id), (string)$request['amount'], 2),
                'description' => $request['description'] ?? null,
                'payment_method' => $request['payment_method'],
                'date' => now()->format('Y-m-d'),
            ]);

            $wallet = Wallet::firstOrCreate(
                ['teacher_id' => $invoice->fee->teacher_id],
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

    public function refundInvoice($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request) {
            $invoice = Invoice::with([
                'studentFee',
                'fee:id,teacher_id',
                'fee.teacher:id',
                'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
                ])
                ->whereIn('status', [1, 2, 3])
                ->findOrFail($id);

            if ($validationResult = $this->validateRefundData($id, $request['amount'])) {
                return $validationResult;
            }

            $netPaid = $invoice->transactions->sum('amount');
            $remaining = bcsub((string)$invoice->amount, (string)$netPaid, 2);

            if ($remaining <= 0 && $invoice->studentFee->is_exempted) {
                $today = now()->startOfDay()->toDateString();
                $newStatus = $invoice->due_date < $today ? 3 : 1;
                $invoice->update(['status' => $newStatus]);
                return $this->successResponse(trans('main.added', ['item' => trans('main.refund')]));
            }

            Transaction::create([
                'type' => 3,
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'amount' => -bcadd((string)$request['amount'], '0', 2),
                'balance_after' => bcsub((string)$this->getTeacherWalletBalance($invoice->fee->teacher_id), (string)$request['amount'], 2),                'description' => $request['description'] ?? null,
                'payment_method' => $request['payment_method'],
                'date' => now()->format('Y-m-d'),
            ]);

            $wallet = Wallet::where('teacher_id', $invoice->fee->teacher_id)->lockForUpdate()->firstOrFail();
            $wallet->decrement('balance', $request['amount']);

            $netPaidAfterRefund = bcsub((string)$netPaid, (string)$request['amount'], 2);
            $today = now()->startOfDay()->toDateString();
            if ($netPaidAfterRefund < $invoice->amount) {
                $newStatus = $invoice->due_date < $today ? 3 : 1;
                $invoice->update(['status' => $newStatus]);
            }

            return $this->successResponse(trans('main.added', ['item' => trans('main.refund')]));
        }, trans('toasts.invoiceNotRefundable'));
    }

    public function deleteInvoice($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $invoice = Invoice::with([
                'transactions' => fn($query) => $query->whereIn('type', [1, 2, 3]),
                'fee:id,teacher_id',
            ])
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->withTrashed()
            ->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($invoice))
                return $dependencyCheck;

            if ($invoice->status == 2) {
                return $this->errorResponse(trans('toasts.cannotDeletePaidInvoice'));
            }

            $walletAdjustment = $invoice->transactions->sum(function ($transaction) {
                return $transaction->type == 2 ? -bcadd((string)$transaction->amount, '0', 2) : (
                    $transaction->type == 3 ? bcadd((string)abs($transaction->amount), '0', 2) : 0
                );
            });

            if ($walletAdjustment != 0) {
                $wallet = Wallet::where('teacher_id', $invoice->fee->teacher_id)->lockForUpdate()->firstOrFail();
                $newBalance = bcadd((string)$wallet->balance, (string)$walletAdjustment, 2);
                if ($newBalance < 0) {
                    return $this->errorResponse(trans('toasts.insufficientWalletBalance'));
                }
                $wallet->update(['balance' => $newBalance]);
            }

            $invoice->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/invoices.invoice')]));
        }, trans('toasts.ownershipError'));
    }

    public function cancelInvoice($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $invoice = Invoice::with([
                'transactions' => fn($query) => $query->whereIn('type', [1, 2, 3]),
                'fee:id,teacher_id',
                'student'
            ])
            ->findOrFail($id);

            if ($invoice->status == 2) {
                return $this->errorResponse(trans('toasts.cannotCancelPaidInvoice'));
            }

            if ($invoice->status == 4) {
                return $this->errorResponse(trans('toasts.invoiceAlreadyCanceled'));
            }

            $walletAdjustment = $invoice->transactions->sum(function ($transaction) {
                return $transaction->type == 2 ? -bcadd((string)$transaction->amount, '0', 2) : (
                    $transaction->type == 3 ? bcadd((string)abs($transaction->amount), '0', 2) : 0
                );
            });

            if ($walletAdjustment != 0) {
                $wallet = Wallet::where('teacher_id', $invoice->fee->teacher_id)->lockForUpdate()->firstOrFail();
                $newBalance = bcadd((string)$wallet->balance, (string)$walletAdjustment, 2);
                if ($newBalance < 0) {
                    return $this->errorResponse(trans('toasts.insufficientWalletBalance'));
                }
                $wallet->update(['balance' => $newBalance]);
            }

            // Transaction::where('invoice_id', $invoice->id)->delete();

            $invoice->update(['status' => 4]);

            return $this->successResponse(trans('main.canceledE', ['item' => trans('admin/invoices.invoice')]));
        });
    }

    public function checkDependenciesForSingleDeletion($invoice)
    {
        return $this->checkForSingleDependencies($invoice, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($invoices)
    {
        return $this->checkForMultipleDependencies($invoices, $this->relationships, $this->transModelKey);
    }
}
