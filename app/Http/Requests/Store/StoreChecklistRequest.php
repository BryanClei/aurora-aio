<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "store_id" => [
                "required",
                "integer",
                "exists:stores,id",
                $this->route("store_checklist")
                    ? "unique:store_checklists,store_id," .
                        $this->route("store_checklist") .
                        ",id,checklist_id," .
                        $this->input("checklist_id")
                    : "unique:store_checklists,store_id,NULL,id,checklist_id," .
                        $this->input("checklist_id"),
            ],
            "store_name" => ["required", "string"],
            "checklist_id" => ["required", "integer", "exists:checklists,id"],
            "checklist_name" => ["required", "string"],
        ];
    }

    public function messages(): array
    {
        return [
            "store_id.required" => "The :attribute field is required.",
            "store_id.integer" => "The :attribute must be an integer.",
            "store_id.exists" => "The selected :attribute is invalid.",
            "store_id.unique" =>
                "The selected :attribute and checklist combination has already been taken.",

            "store_name.required" => "The :attribute field is required.",

            "checklist_id.required" => "The :attribute field is required.",
            "checklist_id.integer" => "The :attribute must be an integer.",
            "checklist_id.exists" => "The selected :attribute is invalid.",

            "checklist_name.required" => "The :attribute field is required.",
        ];
    }

    public function attributes(): array
    {
        return [
            "store_id" => "store",
            "store_name" => "store",
            "checklist_id" => "checklist",
            "checklist_name" => "checklist",
        ];
    }

    /**
     * Customize error messages after validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $errors = $validator->errors();

            if (
                $errors->has("store_id") &&
                $this->filled("store_name") &&
                str_contains(implode(" ", $errors->get("store_id")), "invalid")
            ) {
                $errors->forget("store_id");
                $errors->add(
                    "store_id",
                    "The selected store {$this->store_name} is invalid."
                );
            }

            if (
                $errors->has("checklist_id") &&
                $this->filled("checklist_name") &&
                str_contains(
                    implode(" ", $errors->get("checklist_id")),
                    "invalid"
                )
            ) {
                $errors->forget("checklist_id");
                $errors->add(
                    "checklist_id",
                    "The selected checklist {$this->checklist_name} is invalid."
                );
            }
        });
    }
}
