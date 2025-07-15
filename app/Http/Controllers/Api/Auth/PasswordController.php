<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Password\ChangePasswordRequest;

class PasswordController extends Controller
{
    public function changedPassword(ChangePasswordRequest $request)
    {
        $user = auth("sanctum")->user();

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
}
