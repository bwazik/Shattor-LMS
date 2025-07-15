<?php

namespace App\Http\Requests\Admin\Activities;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'grade_id' => 'required|integer|exists:grades,id',
            'attendance' => 'required|array|min:1',
            'attendance.*.student_id' => 'required|integer|exists:students,id',
            'attendance.*.status' => 'nullable|integer|in:1,2,3,4',
            'attendance.*.note' => 'nullable|string|max:255',
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

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $missingCount = collect($this->input('attendance', []))
    //             ->filter(fn($student) => empty($student['status']))
    //             ->count();

    //         if ($missingCount > 0) {
    //             $validator->errors()->add(
    //                 'attendance',
    //                 trans('admin/attendance.missingStatuses', ['count' => $missingCount])
    //             );
    //         }
    //     });
    // }


    public function messages()
    {
        return [
        ];
    }
}
