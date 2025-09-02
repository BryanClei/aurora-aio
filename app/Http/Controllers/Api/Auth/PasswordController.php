<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthServices\AuthService;
use App\Http\Requests\Password\ChangePasswordRequest;

class PasswordController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function changedPassword(ChangePasswordRequest $request)
    {
        $user = $this->authService->changePassword(
            Auth::id(),
            $request->new_password
        );

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                "Please make sure you are logged in"
            );
        }

        return $this->responseSuccess("Password changed successfully");
    }

    public function resetPassword($id)
    {
        $user = $this->authService->resetPassword($id);

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        return $this->responseSuccess("The Password has been reset");
    }
}
