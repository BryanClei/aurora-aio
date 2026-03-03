<?php

namespace App\Http\Requests\Allowable;

use App\Models\AllowableDays;
use Illuminate\Foundation\Http\FormRequest;

class AllowableRequest extends FormRequest
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
            "days" => [
                "required",
                "numeric",
                "min:0",
                "max:30",
                $this->route()->allowable_day
                    ? "unique:allowable_days,id," .
                        $this->route()->allowable_day
                    : function ($attribute, $value, $fail) {
                        if (AllowableDays::count() >= 1) {
                            $fail("Only one configuration record is allowed.");
                        }
                    },
            ],
        ];
    }
}
