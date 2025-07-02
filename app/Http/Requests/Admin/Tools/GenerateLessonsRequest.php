<?php

namespace App\Http\Requests\Admin\Tools;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLessonsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after:start_date',
        ];
    }

    public function messages()
    {
        return [
        ];
    }
}
