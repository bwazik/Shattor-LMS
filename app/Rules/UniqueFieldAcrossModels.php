<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Student;
use App\Models\MyParent;
use App\Models\Assistant;
use App\Models\Teacher;

class UniqueFieldAcrossModels implements ValidationRule
{
    protected $exceptModelId;
    protected $field;
    protected $errorMessage;

    // Constructor to optionally exclude the model's ID and specify the field (email, username, or phone)
    public function __construct($field, $exceptModelId = null)
    {
        $this->exceptModelId = $exceptModelId;
        $this->field = $field;
        $this->errorMessage = trans('toasts.fieldAlreadyInUse', ['field' => $field]);
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // List of models to check for the field's uniqueness
        $models = [
            Student::class,
            MyParent::class,
            Assistant::class,
            Teacher::class,
        ];

        if (!isAdmin()) {
            foreach ($models as $model) {
                // Check if the field exists in the model
                $query = $model::where($this->field, $value);

                // Exclude the current record from the check (for update operations)
                if ($this->exceptModelId) {
                    // Check if the identifier is a UUID or numeric ID
                    if (is_numeric($this->exceptModelId)) {
                        $query->where('id', '!=', $this->exceptModelId);
                    } else {
                        $query->where('uuid', '!=', $this->exceptModelId);
                    }
                }

                // If the field value exists, fail validation
                // if ($query->exists()) {
                //     $fail($this->errorMessage);
                //     return; // Stop further checks once a match is found
                // }
            }
        }
    }
}
