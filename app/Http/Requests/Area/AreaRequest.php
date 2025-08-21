<?php

namespace App\Http\Requests\Area;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AreaRequest extends FormRequest
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
                $this->route()->area
                    ? "unique:areas,name," . $this->route()->area
                    : "unique:areas,name",
            ],
            "region_id" => [
                "required",
                Rule::exists("regions", "id")->whereNull("deleted_at"),
            ],
            "area_head_id" => [
                "required",
                Rule::exists("users", "id")->whereNull("deleted_at"),
            ],
            "area_list" => "array",
        ];
    }

    public function attributes(): array
    {
        return [
            "region_id" => "region",
            "area_head_id" => "area head",
        ];
    }

    public function messages(): array
    {
        return [
            "unique" => "The selected :attribute has already been taken.",
            "exists" => "The selected :attribute does not exist.",
        ];
    }
}
