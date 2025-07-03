<?php

namespace App\Http\Requests\Admin\Activities;

use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'uuid' => 'required|string|uuid|exists:students,uuid',
            'grade_id' => 'required|integer|exists:grades,id',
            'group_id' => 'required|string|uuid|exists:groups,uuid',
            'lesson_id' => 'required|string|uuid|exists:lessons,uuid',
        ];
    }

    public function messages()
    {
        return [
        ];
    }
}
