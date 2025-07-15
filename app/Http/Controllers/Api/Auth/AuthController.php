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

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $user = User::with("role")
            ->where("username", $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->responseBadRequest("", "Invalid Credentials");
        }

        $token = $user->createToken("PersonalAccessToken")->plainTextToken;
        $user["token"] = $token;

        $cookie = cookie("authcookie", $token);

        return response()
            ->json(
                [
                    "message" => "Successfully Logged In",
                    "token" => $token,
                    "data" => array_merge($user->toArray(), [
                        "should_change_password" =>
                            (bool) ($request->username === $request->password),
                    ]),
                ],
                200
            )
            ->withCookie($cookie);
    }

    public function Logout(Request $request)
    {
        Cookie::forget("authcookie");
        auth("sanctum")
            ->user()
            ->currentAccessToken()
            ->delete();
        return $this->responseSuccess("Logout successfully");
    }
}
