<?php

namespace App\Http\Requests;

use App\Models\Grade;
use Illuminate\Support\Facades\Auth;
use App\Rules\UniqueFieldAcrossModels;
use Illuminate\Foundation\Http\FormRequest;

class PersonalDataRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        if(isTeacher())
        {
            $rules = [
                'username' => ['required','min:5','max:20',new UniqueFieldAcrossModels('username', Auth::user()->id)],
                'name_ar' => 'required|min:3|max:100',
                'name_en' => 'required|min:3|max:100',
                'phone' => ['required','numeric','regex:/^(01)[0-9]{9}$/',new UniqueFieldAcrossModels('phone', Auth::user()->id)],
                'email' => ['nullable','email','max:100',new UniqueFieldAcrossModels('email', Auth::user()->id)],
                'subject_id' => 'required|integer|exists:subjects,id',
                'grades' => 'required|array|min:1',
                'grades.*' => 'integer|exists:grades,id',
            ];
        }
        elseif(isStudent())
        {
            $rules = [
                'name_en' => 'required|min:3|max:100',
                'username' => ['required','min:5','max:20',new UniqueFieldAcrossModels('username', Auth::user()->id)],
                'email' => ['nullable','email','max:100',new UniqueFieldAcrossModels('email', Auth::user()->id)],
                'birth_date' => 'nullable|date|date_format:Y-m-d',
                'gender' => 'required|integer|in:1,2',
                'specialization' => 'required|integer|in:1,2',
            ];
        }
        elseif(isParent())
        {
            $rules = [
                'username' => ['required','min:5','max:20',new UniqueFieldAcrossModels('username', Auth::user()->id)],
                'name_ar' => 'required|min:3|max:100',
                'name_en' => 'required|min:3|max:100',
                'phone' => ['required','numeric','regex:/^(01)[0-9]{9}$/',new UniqueFieldAcrossModels('phone', Auth::user()->id)],
                'email' => ['nullable','email','max:100',new UniqueFieldAcrossModels('email', Auth::user()->id)],
                'gender' => 'required|integer|in:1,2',
            ];
        }

        return $rules;
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
