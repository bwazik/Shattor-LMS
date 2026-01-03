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
        $parentKey = isAdmin() ? 'id' : 'uuid';

        $rules = [
            'username' => ['required','min:5','max:20',new UniqueFieldAcrossModels('username', $this->id)],
            'password' => $isUpdate ? 'nullable|min:8|max:50' : 'required|min:8|max:50',
            'name_ar' => 'required|min:3|max:100',
            'name_en' => 'required|min:3|max:100',
            'phone' => ['required', 'numeric', 'regex:/^(010|011|012|015)\d{8}$/', "unique:parents,phone,{$this->id},{$parentKey}"],
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
            'username.unique' => 'اسم المستخدم ده مستخدم قبل كده، جرّب تختار اسم تاني.',
            'email.unique' => 'الإيميل ده مسجّل بالفعل، من فضلك استخدم إيميل مختلف.',
            'phone.unique' => 'الرقم ده موجود قبل كده، لازم تدخل رقم جديد.',
            'phone.numeric' => 'من فضلك دخل أرقام بس في خانة التليفون.',
            'phone.regex' => 'رقم التليفون لازم يبدأ بـ 01 ويتكون من 11 رقم.',
        ];
    }
}
