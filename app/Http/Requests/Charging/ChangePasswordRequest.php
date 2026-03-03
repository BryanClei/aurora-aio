<?php

namespace App\Http\Requests\Charging;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ChangePasswordRequest extends FormRequest
{
    public function rules()
    {
        return [
            "old_password" => ["required"],
            "password"     => ["required", "different:old_password"],
        ];
    }

    public function attributes()
    {
        return [
            "old_password" => "Old password",
            "password"     => "New password",
        ];
    }

    public function messages()
    {
        return [
            "required"  => "The :attribute is required.",
            "different" => ":attribute must be different from the old password.",
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $id = $this->route('id');

            [$prefix, $number] = explode("-", $id);

            $user = User::where("id_prefix", $prefix)
                ->where("id_no", $number)
                ->first();

            if (!$user) {
                $validator->errors()->add('account_code', 'User not found.');
                return;
            }

            $oldPasswordMatches = $user->password === $this->old_password
                || Hash::check($this->old_password, $user->password);

            if (!$oldPasswordMatches) {
                $validator->errors()->add('old_password', 'The Old password is incorrect.');
            }
        });
    }
}
