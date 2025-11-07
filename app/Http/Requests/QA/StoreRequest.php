<?php

namespace App\Http\Requests\QA;

use Carbon\Carbon;
use App\Models\StoreChecklist;
use App\Rules\WeeklyLimitRule;
use Illuminate\Validation\Rule;
use App\Helpers\FourWeekCalendarHelper;
use App\Models\StoreChecklistWeeklyRecord;
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
            "responses.*.answer" => ["nullable"],
            "responses.*.remarks" => ["nullable"],
            "responses.*.attachment" => [
                "nullable",
                "image",
                "mimes:jpeg,jpg,png,gif,webp",
                "max:10240",
            ],
            "responses.*.attachment.*" => [
                "nullable",
                "image",
                "mimes:jpeg,jpg,png,gif,webp",
                "max:10240",
            ],
            "store_visit" => ["nullable"],
            "expired" => ["nullable"],
            "condemned" => ["nullable"],
            "good_points" => ["nullable", "string", "max:2000"],
            "notes" => ["nullable", "string", "max:2000"],
            "store_duty_id" => ["required", "array", "min:1"],
            "store_duty_id.*" => ["required", "integer", "exists:users,id"],
        ];

        foreach ($this->input("responses", []) as $index => $response) {
            if (
                isset($response["question_type"]) &&
                $response["question_type"] !== "paragraph"
            ) {
                $rules["responses.$index.answer"] = ["required"];
            }
        }

        return $rules;
    }

    /**
     * Additional validation after base rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $storeChecklist = StoreChecklist::find($this->store_checklist_id);

            if (!$storeChecklist) {
                $validator
                    ->errors()
                    ->add(
                        "store_checklist_id",
                        "The selected store checklist does not exist."
                    );
                return;
            }

            if (
                $storeChecklist->store_id != $this->store_id ||
                $storeChecklist->checklist_id != $this->checklist_id
            ) {
                $validator
                    ->errors()
                    ->add(
                        "store_checklist_id",
                        "The selected store checklist does not match the provided store or checklist."
                    );
            }

            $today = Carbon::today();
            $fourWeekInfo = FourWeekCalendarHelper::getMonthBasedFourWeek(
                $today
            );

            $week = $fourWeekInfo["week"];
            $month = $fourWeekInfo["month"];
            $year = $fourWeekInfo["year"];

            $alreadyAnswered = StoreChecklistWeeklyRecord::where(
                "store_checklist_id",
                $this->store_checklist_id
            )
                ->where("week", $week)
                ->where("month", $month)
                ->where("year", $year)
                ->exists();

            if ($alreadyAnswered) {
                $validator
                    ->errors()
                    ->add(
                        "store_checklist_id",
                        "You have already submitted a weekly survey for this store checklist (Week " .
                            $week .
                            ", " .
                            $month .
                            " " .
                            $year .
                            ")."
                    );
            }
        });
    }

    public function messages(): array
    {
        return [
            "responses.*.attachment.image" =>
                "The attachment must be an image file.",
            "responses.*.attachment.mimes" =>
                "The attachment must be a file of type: jpeg, jpg, png, gif, webp.",
            "responses.*.attachment.max" =>
                "The attachment may not be greater than 10MB.",
            "responses.*.attachment.*.image" =>
                "All attachments must be image files.",
            "responses.*.attachment.*.mimes" =>
                "All attachments must be files of type: jpeg, jpg, png, gif, webp.",
            "responses.*.attachment.*.max" =>
                "Each attachment may not be greater than 10MB.",
        ];
    }
}
