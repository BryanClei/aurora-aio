<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            "personal_info.id_prefix" => "sometimes|required",
            "personal_info.id_no" => [
                "sometimes",
                "required",
                function ($attribute, $value, $fail) {
                    $prefix = request()->input("personal_info.id_prefix");

                    if ($prefix && $value) {
                        $exists = User::where("id_prefix", $prefix)
                            ->where("id_no", $value)
                            ->exists();

                        if ($exists) {
                            $fail(
                                "The combination of prefix and ID number already exists."
                            );
                        }
                    }
                },
            ],
            "personal_info.first_name" => "sometimes:required",
            "personal_info.last_name" => "sometimes:required",
            "personal_info.mobile_number" => [
                "nullable",
                "unique:users,mobile_number," . $this->route()->user,
                "regex:/^\+63\d{10}$/",
            ],
            "personal_info.gender" => "sometimes|required|in:male,female",
            "personal_info.one_charging_id" => [
                "required",
                "exists:one_charging,sync_id",
            ],
            "username" => [
                "required",
                "unique:users,username," . $this->route()->user,
            ],
            "role_id" => ["required", "exists:roles,id"],
        ];
    }

    public function messages(): array
    {
        return [
            "personal_info.id_prefix.unique" =>
                "The id prefix has already been taken",
            "personal_info.id_prefix.required" =>
                "The id prefix field is required",
            "personal_info.id_no.unique" =>
                "The employee id has already been taken",
            "personal_info.id_no.required" =>
                "The employee id field is required",
            "personal_info.first_name.required" =>
                "The first name field is required.",
            "personal_info.last_name.required" =>
                "The last name field is required.",
            "personal_info.mobile_number.regex" =>
                "The mobile number field format is invalid.",
            "personal_info.mobile_number.unique" =>
                "The contact number :input has already been taken.",
            "personal_info.gender.required" => "The gender field is required.",
            "personal_info.gender.in" =>
                "The gender must be either 'male' or 'female'.",
            "personal_info.one_charging_id.required" =>
                "The one charging field is required.",
            "personal_info.one_charging_id.exists" =>
                "The selected one charging is invalid",
            "username.unique" => "The username :input has already been taken.",
        ];
    }
}
