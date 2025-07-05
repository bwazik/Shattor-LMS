<?php

namespace App\Http\Requests\Admin\Activities;

use Illuminate\Foundation\Http\FormRequest;

class CompensatoriesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'reason' => 'required|string|max:1000',
        ];

        if (isAdmin()) {
            $rules['student_id'] = 'required|integer|exists:students,id';
            $rules['original_lesson_id'] = 'required|integer|exists:lessons,id';
            $rules['makeup_lesson_id'] = 'required|integer|exists:lessons,id|different:original_lesson_id';
        } else {
            $rules['original_lesson_id'] = 'required|string|uuid|exists:lessons,uuid';
            $rules['makeup_lesson_id'] = 'required|string|uuid|exists:lessons,uuid|different:original_lesson_id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
        ];
    }
}
