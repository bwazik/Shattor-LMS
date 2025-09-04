<?php

namespace App\Http\Controllers\Admin\Misc;

use Illuminate\Http\Request;
use App\Models\WhatsappMessage;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Admin\Misc\WhatsappMessageService;

class WhatsappMessagesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $whatsappMessagesQuery;

    public function __construct(WhatsappMessageService $whatsappMessageService)
    {
        $this->whatsappMessagesQuery = $whatsappMessageService;
    }

    public function index(Request $request)
    {
        $whatsappMessagesQuery = WhatsappMessage::query()
            ->select('id', 'phone', 'template', 'data', 'status', 'error_message', 'attempts', 'sent_at', 'created_at');

        if ($request->ajax()) {
            return $this->whatsappMessagesQuery->getWhatsappMessagesForDatatable($whatsappMessagesQuery);
        }

        return view('admin.misc.whatsappMessages.index');
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'whatsapp_messages');

        $result = $this->whatsappMessagesQuery->deleteWhatsappMessage($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'whatsapp_messages');

        $result = $this->whatsappMessagesQuery->deleteWhatsappMessages($request->ids);

        return $this->conrtollerJsonResponse($result);
    }
}
