<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
                $this->route()->role
                    ? "unique:roles,name," . $this->route()->role
                    : "unique:roles,name",
            ],
            "access_permission" => ["required", "array", "distinct", "min:1"],
            "access_permission.*" => ["distinct", "required"],
        ];
    }
}
