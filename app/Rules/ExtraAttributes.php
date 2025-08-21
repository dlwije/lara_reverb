<?php

namespace App\Rules;

use Closure;
use App\Models\Sma\Setting\CustomField;
use Illuminate\Contracts\Validation\ValidationRule;

class ExtraAttributes implements ValidationRule
{
    /**
     * Model name to get custom fields.
     *
     * @var string
     */
    protected $fieldsOf;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $fieldsOf)
    {
        $this->fieldsOf = $fieldsOf;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fields = CustomField::ofModel($this->fieldsOf)->get();
        foreach ($fields as $field) {
            if ($field && $field->is_required && ! ($value[$field->name] ?? null)) {
                $fail($attribute . '.' . $field->name, trans('validation.required', ['attribute' => str($field->name)->lower()]));
            }
        }
    }
}
