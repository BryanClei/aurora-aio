<?php

namespace App\Http\Requests\Password;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            "old_password" => "required|current_password|different:field",
            "new_password" => [
                "required",
                "different:old_password",
                "not_in:" . auth()->user()->username,
                "confirmed",
            ],

            // "confirm_password" => "required|same:new_password",
        ];
    }

    public function attributes(): array
    {
        return [
            "old_password" => "old password",
            "new_password" => "new password",
            // "confirm_password" => "confirm password",
        ];
    }

    public function messages(): array
    {
        return [
            "required" => "The :attribute is required.",
            "current_password" => "The :attribute is incorrect.",
            "different" =>
                ":attribute must be different from the old password.",
            // "same" => "The :attribute does not match with new password.",
        ];
    }
}
