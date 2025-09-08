<?php

namespace App\Http\Requests\Admin\Activities;

use App\Models\OfflineQuiz;
use Illuminate\Foundation\Http\FormRequest;

class OfflineQuizzesScoresRequest extends FormRequest
{
    protected $maxScore;

    protected function prepareForValidation()
    {
        $uuid = $this->route('uuid');

        $offlineQuiz = OfflineQuiz::uuid($uuid)->select('score')->first();
        $this->maxScore = $offlineQuiz?->score;
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'scores' => 'required|array',
            'scores.*.score' => 'required|numeric|min:0|max:' . $this->maxScore,
            'scores.*.note' => 'nullable|string|max:1000',
        ];

        if (isAdmin()) {
            $rules['scores.*.student_id'] = 'required|integer|exists:students,id';
        } else {
            $rules['scores.*.student_id'] = 'required|string|uuid|exists:students,uuid';
        }

        return $rules;
    }

    public function messages()
    {
        return [
        ];
    }
}
