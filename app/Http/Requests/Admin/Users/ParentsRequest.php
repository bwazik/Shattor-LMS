<?php

namespace App\Http\Requests\Admin\Users;

use App\Rules\UniqueFieldAcrossModels;
use Illuminate\Foundation\Http\FormRequest;

class ParentsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isUpdate = $this->id ? true : false;

        $rules = [
            'username' => ['required','min:5','max:20',new UniqueFieldAcrossModels('username', $this->id)],
            'password' => $isUpdate ? 'nullable|min:8|max:50' : 'required|min:8|max:50',
            'name_ar' => 'required|min:3|max:100',
            'name_en' => 'required|min:3|max:100',
            'phone' => $isUpdate ? 'required|numeric|regex:/^(01)[0-9]{9}$/' : 'required|numeric|regex:/^(01)[0-9]{9}$/|unique:parents,phone',
            'email' => ['nullable','email','max:100',new UniqueFieldAcrossModels('email', $this->id)],
            'gender' => 'required|integer|in:1,2',
            'is_active' => 'nullable|boolean',
        ];

        if (isAdmin()) {
            $rules['students'] = 'required|array|min:1';
            $rules['students.*'] = 'integer|exists:students,id';
        } else {
            $rules['students'] = 'required|array|min:1';
            $rules['students.*'] = 'string|uuid|exists:students,uuid';
        }

        return $rules;
    }

    public function messages()
    {
        return [
        ];
    }
}
