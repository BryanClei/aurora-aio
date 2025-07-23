<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Password\ChangePasswordRequest;

class PasswordController extends Controller
{
    use ApiResponse;

    public function changedPassword(ChangePasswordRequest $request)
    {
        $id = Auth::id();
        $user = User::find($id);

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                "Please make sure you are logged in"
            );
        }

        $user->update([
            "password" => Hash::make($request->new_password),
        ]);

        return $this->responseSuccess("Password change successfully");
    }

    public function resetPassword($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        $user->update([
            "password" => $user->username,
        ]);

        return $this->responseSuccess("The Password has been reset");
    }
}
