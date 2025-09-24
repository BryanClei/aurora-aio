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
            "sections" => ["required", "array"],
            "sections.*.title" => ["required", "string"],
            "sections.*.order_index" => ["required", "integer"],
            "sections.*.questions" => ["required", "array"],
            "sections.*.questions.*.question_text" => ["required", "string"],
            "sections.*.questions.*.question_type" => [
                "required",
                "in:text,multiple_choice,checkboxes,paragraph",
            ],
            "sections.*.questions.*.order_index" => ["required", "integer"],
            "sections.*.questions.*.options" => [
                "required_if:sections.*.questions.*.question_type,multiple_choice,checkboxes",
                "array",
            ],
            "sections.*.questions.*.options.*.option_text" =>
                "required_with:sections.*.questions.*.options|string",
            "sections.*.questions.*.options.*.order_index" =>
                "required_with:sections.*.questions.*.options|integer",
        ];
    }

    public function messages(): array
    {
        return [
            "unique" => "The :attribute :input is already taken",
            "required" => "The :attribute field is required",
            "required_if" => "The :attribute field is required",
            "required_with" => "The :attribute field is required",
            "array" => "The :attribute must be an array",
            "string" => "The :attribute must be a string",
            "integer" => "The :attribute must be an integer",
            "in" => "The selected :attribute :input is invalid",
        ];
    }
}
