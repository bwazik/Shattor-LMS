<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class RestrictFinancials
{
    public function handle(Request $request, Closure $next)
    {
        // Check if PIN was just verified for this request
        if (Session::pull('pin_verified') || $request->user()->financal_pin === null) {
            return $next($request);
        }

        // Store the intended URL for redirect after PIN verification
        Session::put('intended_url', $request->fullUrl());

        return redirect()->route('teacher.verify-pin.index')->with('error', 'Please enter your PIN to access financial data.');
    }
}
