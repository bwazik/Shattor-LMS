<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\Grade;
use App\Rules\UniqueFieldAcrossModels;
use Illuminate\Foundation\Http\FormRequest;

class TeachersRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isUpdate = $this->id ? true : false;

        return [
            'username' => ['required','min:5','max:20',new UniqueFieldAcrossModels('username', $this->id)],
            'password' => $isUpdate ? 'nullable|min:8|max:50' : 'required|min:8|max:50',
            'name_ar' => 'required|min:3|max:100',
            'name_en' => 'required|min:3|max:100',
            'phone' => 'required|numeric|regex:/^(01)[0-9]{9}$/|unique:teachers,phone,' . $this->id,
            'email' => ['nullable','email','max:100',new UniqueFieldAcrossModels('email', $this->id)],
            'subject_id' => 'required|integer|exists:subjects,id',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'is_active' => 'nullable|boolean',
            'grades' => 'required|array|min:1',
            'grades.*' => 'integer|exists:grades,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->grades) {
                foreach ($this->grades as $grade_id) {
                    if (!is_numeric($grade_id) || (int)$grade_id != $grade_id) {
                        $validator->errors()->add('grades', 'Each grade ID must be an integer.');
                    }
                    if (!Grade::where('id', $grade_id)->exists()) {
                        $validator->errors()->add('grades', 'One of the selected grades is invalid.');
                    }
                }
            }
        });
    }

    public function messages()
    {
        return [
        ];
    }
}
