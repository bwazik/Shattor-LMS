<?php

namespace App\Http\Requests\Admin\Tools;

use Illuminate\Foundation\Http\FormRequest;

class GroupsRequest extends FormRequest
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
            'day_1' => 'required|integer|between:1,7',
            'day_2' => 'required|integer|between:1,7',
            'time' => 'required|date_format:H:i',
            'is_active' => 'nullable|boolean',
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
