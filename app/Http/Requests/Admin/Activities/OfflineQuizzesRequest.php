<?php

namespace App\Http\Requests\Admin\Activities;

use Illuminate\Foundation\Http\FormRequest;

class OfflineQuizzesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name_ar' => 'required|min:3|max:100',
            'name_en' => 'required|min:3|max:100',
            'grade_id' => 'required|integer|exists:grades,id',
            'type' => 'required|in:1,2',
            'score' => 'required|numeric|min:0|max:500',
            'conducted_at' => 'required|date|date_format:Y-m-d',
        ];

        if (isAdmin()) {
            $rules['teacher_id'] = 'required|integer|exists:teachers,id';
            $rules['groups'] = 'required|array|min:1';
            $rules['groups.*'] = 'required|integer|exists:groups,id';
        } else {
            $rules['groups'] = 'required|array|min:1';
            $rules['groups.*'] = 'required|string|uuid|exists:groups,uuid';
        }

        return $rules;
    }

    public function messages()
    {
        return [
        ];
    }
}
