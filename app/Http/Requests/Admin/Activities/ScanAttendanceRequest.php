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
        $rules = [
            'uuid' => 'required|string|uuid|exists:students,uuid',
            'grade_id' => 'required|integer|exists:grades,id',
            'group_id' => 'required|string|uuid|exists:groups,uuid',
            'lesson_id' => 'required|string|uuid|exists:lessons,uuid',
        ];

        if (isAdmin()) {
            $rules['teacher_id'] = 'required|integer|exists:teachers,id';
            $rules['group_id'] = 'required|integer|exists:groups,id';
            $rules['lesson_id'] = 'required|integer|exists:lessons,id';
        } else {
            $rules['group_id'] = 'required|string|uuid|exists:groups,uuid';
            $rules['lesson_id'] = 'required|string|uuid|exists:lessons,uuid';
        }

        return $rules;
    }

    public function messages()
    {
        return [
        ];
    }
}
