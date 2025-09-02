<?php

namespace App\Http\Requests\Region;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegionRequest extends FormRequest
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
                $this->route()->region
                    ? "unique:regions,name," . $this->route()->region
                    : "unique:regions,name",
            ],
            "region_head_id" => [
                "required",
                Rule::exists("users", "id")->whereNull("deleted_at"),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            "region_head_id" => "region head",
        ];
    }

    public function messages(): array
    {
        return [
            "required" => "The :attribute is required.",
            "unique" => "The selected :attribute has already been taken.",
            "exists" => "The selected :attribute does not exist.",
        ];
    }
}
