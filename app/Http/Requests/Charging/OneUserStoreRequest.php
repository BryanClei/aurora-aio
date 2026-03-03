<?php

namespace App\Http\Requests\Charging;

use Illuminate\Foundation\Http\FormRequest;

class OneUserStoreRequest extends FormRequest
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
            "id_prefix" => "required",
            "id_no" => "required",
            "username" => "required",
            "password" => "required",
            "first_name" => "required",
            "middle_name" => "nullable",
            "last_name" => "required",
            "suffix_name" => "nullable",
        ];
    }
}
