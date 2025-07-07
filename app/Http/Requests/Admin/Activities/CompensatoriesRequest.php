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
        if (isAdmin()) {
            return [
                'student_id' => 'required|integer|exists:students,id',
                'original_lesson_id' => 'required|integer|exists:lessons,id',
                'makeup_lesson_id' => 'required|integer|exists:lessons,id|different:original_lesson_id',
                'reason' => 'required|string|max:1000',
            ];
        } elseif (isTeacher()) {
            return [
                'student_id' => 'required|string|uuid|exists:students,uuid',
                'original_lesson_id' => 'required|string|uuid|exists:lessons,uuid',
                'makeup_lesson_id' => 'required|string|uuid|exists:lessons,uuid|different:original_lesson_id',
                'reason' => 'required|string|max:1000',
            ];
        } elseif(isStudent()) {
            return [
                'original_lesson_id' => 'required|string|uuid|exists:lessons,uuid',
                'makeup_lesson_id' => 'required|string|uuid|exists:lessons,uuid|different:original_lesson_id',
                'reason' => 'required|string|max:1000',
            ];
        }
    }

    public function messages()
    {
        return [
        ];
    }
}
