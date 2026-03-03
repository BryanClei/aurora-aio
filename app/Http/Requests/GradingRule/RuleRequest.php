<?php

namespace App\Http\Requests\GradingRule;

use App\Models\GradingRule;
use Illuminate\Foundation\Http\FormRequest;

class RuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "cap_percentage" => [
                "required",
                "numeric",
                "min:0",
                "max:100",
                $this->route()->grade_rule
                    ? "unique:grading_rule,id," . $this->route()->grade_rule
                    : function ($attribute, $value, $fail) {
                        if (GradingRule::count() >= 1) {
                            $fail("Only one configuration record is allowed.");
                        }
                    },
            ],
        ];
    }
}
