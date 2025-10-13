<?php

namespace App\Http\Requests\Store;

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
            "name" => [
                "required",
                "string",
                $this->route()->store
                    ? "unique:stores,name," . $this->route()->store
                    : "unique:stores,name",
            ],
            "area_id" => ["required", "exists:areas,id,deleted_at,NULL"],
            "region_id" => [
                "required",
                "exists:regions,id,deleted_at,NULL",
                Rule::unique("stores")
                    ->where(function ($query) {
                        return $query->where("area_id", $this->area_id);
                    })
                    ->ignore($this->route()->store),
            ],
        ];
    }

    public function messages()
    {
        return [
            "region_id.unique" =>
                "The combination of region and area already exists.",
        ];
    }
}
