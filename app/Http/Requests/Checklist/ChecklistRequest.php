<?php

namespace App\Http\Requests\Checklist;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistRequest extends FormRequest
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
            "name" => [
                "required",
                $this->route()->checklist
                    ? "unique:checklists,name," . $this->route()->checklist
                    : "unique:checklists,name",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "unique" => "The :attribute :input is already taken",
        ];
    }
}
