<?php

namespace App\Http\Requests\QA;

use App\Rules\WeeklyLimitRule;
use Illuminate\Validation\Rule;
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
            "store_checklist_id" => [
                "required",
                "exists:store_checklists,id",
                new WeeklyLimitRule(),
            ],
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
                "exists:checklist_questions,id",
            ],
            "responses.*.question_text" => ["required", "string"],
            "responses.*.question_type" => [
                "required",
                "string",
                Rule::in([
                    "multiple_choice",
                    "checkboxes",
                    "paragraph",
                    "short_answer",
                    "dropdown",
                ]),
            ],
            "responses.*.answer" => ["required"],
            "responses.*.remarks" => ["nullable", "string"],
            "responses.*.attachment" => ["nullable", "string"],
            "store_visit" => ["nullable", "date"],
            "expired" => ["nullable"],
            "condemned" => ["nullable"],
            "good_points" => ["nullable", "string", "max:2000"],
            "notes" => ["nullable", "string", "max:2000"],
            "store_duty_id" => ["required", "array", "min:1"],
            "store_duty_id.*" => ["required", "integer", "exists:users,id"],
        ];
    }
}
