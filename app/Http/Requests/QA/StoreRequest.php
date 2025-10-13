<?php

namespace App\Http\Requests\QA;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            "store_id" => ["required", "exists:stores,id"],
            "checklist_id" => ["required", "integer", "exists:checklists,id"],
            "code" => ["required", "string", "exists:store_checklists,code"],
            "responses" => ["required", "array", "min:1"],
            "responses.*.section_id" => [
                "required",
                "integer",
                "exists:checklist_sections,id",
            ],
            "responses.*.question_id" => [
                "required",
                "integer",
                "exists:questions,id",
            ],
        ];
    }
}
