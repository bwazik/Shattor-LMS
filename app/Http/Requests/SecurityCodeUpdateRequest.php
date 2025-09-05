<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class SecurityCodeUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'currentSecurityCode' => ['required', 'digits:6'],
            'newSecurityCode' => ['required', 'digits:6'],
            'confirmNewSecurityCode' => ['required', 'digits:6', 'same:newSecurityCode'],
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
