<?php

namespace App\Http\Controllers\Teacher\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class VerifyFinancalPinController extends Controller
{
    protected $teacherFinancalPin;

    public function __construct()
    {
        $this->teacherFinancalPin = auth()->guard('teacher')->user()->financal_pin;
    }

    public function index()
    {
        return view('teacher.finance.verify-financal-pin');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:6',
        ]);

        if (Hash::check($request->pin, $this->teacherFinancalPin)) {
            Session::put('pin_verified', true);
            $intendedUrl = Session::pull('intended_url', route('teacher.fees.index'));
            return response()->json(['success' => true, 'message' => trans('toasts.validPin'), 'redirect' => $intendedUrl]);
        }

        return response()->json(['error' => trans('toasts.invalidPin')], 422);
    }
}