<?php

namespace App\Http\Requests\Store;

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
            "code" => [
                "required",
                $this->route()->store
                    ? "unique:stores,code," . $this->route()->store
                    : "unique:stores,code",
            ],
            "area_id" => ["required", "exists:areas,id"],
            "region_id" => ["required", "exists:region,id"],
            "checklist_id" => ["required", "exists:checklists,id"],
        ];
    }
}
