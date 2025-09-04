<?php

namespace App\Services\Admin\Misc;
use App\Models\WhatsappMessage;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class WhatsappMessageService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    public function getWhatsappMessagesForDatatable($whatsappMessagesQuery)
    {
        return datatables()->eloquent($whatsappMessagesQuery)
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->addColumn('status', fn($row) => $this->formatStatusSpan($row->status))
            ->editColumn('sent_at', fn($row) => isoFormat($row->sent_at))
            ->addColumn('data', fn($row) => $this->showDataButton($row->data))
            ->editColumn('error_message', fn($row) => $row->error_message ? $row->error_message : '-')
            ->addColumn('queue', fn($row) => $row->data['is_urgent'] ? trans('admin/whatsappMessages.urgent') : trans('admin/whatsappMessages.default'))
            ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->rawColumns(['selectbox', 'status', 'data', 'actions'])
            ->make(true);
    }

    private function formatStatusSpan($status)
    {
        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-primary text-capitalized">' . trans('admin/whatsappMessages.queued') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('admin/whatsappMessages.sent') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-danger text-capitalized">' . trans('admin/whatsappMessages.failed') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalized">-</span>';
        }
    }

    private function showDataButton($data)
    {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $escapedJson = htmlspecialchars($jsonData, ENT_QUOTES, 'UTF-8');

        return
            '<div class="align-items-center">' .
                '<span class="badge rounded-pill bg-label-info text-capitalized cursor-pointer"
                    id="data-button" data-message-data=\'' . $escapedJson . '\'
                    data-bs-target="#data-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                    ' . trans('main.details') . '
                </span>' .
            '</div>';
    }

    private function generateActionButtons($row): string
    {
        return
            '<div class="align-items-center">' .
                '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light"
                    id="delete-button" data-id=' . $row->id . ' data-phone="' . $row->phone . '"
                    data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                    <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
                </button>' .
            '</div>';
    }

    public function deleteWhatsappMessage($id): array
    {
        return $this->executeTransaction(function () use ($id) {
            WhatsappMessage::findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/whatsappMessages.whatsappMessage')]));
        });
    }

    public function deleteWhatsappMessages($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids) {
            WhatsappMessage::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/whatsappMessageS.whatsappMessage')]));
        });
    }
}
