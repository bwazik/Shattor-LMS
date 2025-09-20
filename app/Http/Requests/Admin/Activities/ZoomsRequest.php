<?php

namespace App\Http\Requests\Admin\Activities;

use Illuminate\Foundation\Http\FormRequest;

class ZoomsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules =  [
            'grade_id' => 'required|integer|exists:grades,id',
            'topic_ar' => 'required|min:3|max:100',
            'topic_en' => 'required|min:3|max:100',
            'duration' => 'required|integer|min:1|max:360',
            'start_time' => 'required|date|after_or_equal:now|date_format:Y-m-d H:i',
            'password' => 'nullable|min:4|max:8',
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
