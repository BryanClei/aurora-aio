<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Services\AuthServices\AuthService;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login(
            $request->username,
            $request->password
        );

        if (!$result) {
            return $this->responseBadRequest("", "Invalid Credentials");
        }

        $user = $result["user"];
        $token = $result["token"];
        $cookie = $result["cookie"];

        return response()
            ->json([
                "message" => "Successfully Logged In",
                "token" => $token,
                "data" => array_merge($user->toArray(), [
                    "should_change_password" =>
                        (bool) $result["should_change_password"],
                ]),
            ])
            ->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->responseSuccess("Logout successfully");
    }
}
