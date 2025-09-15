<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;

class FeesRequest extends FormRequest
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
            'amount' => 'required|numeric|between:0,999999.99',
            'grade_id' => 'required|integer|exists:grades,id',
            'specialization' => 'nullable|integer|in:1,2',
            'frequency' => 'required|integer|in:1,2,3', // 1: one-time, 2: monthly, 3: custom
        ];

        if (isAdmin()) {
            $rules['teacher_id'] = 'required|integer|exists:teachers,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
        ];
    }
}
