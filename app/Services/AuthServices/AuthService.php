<?php

namespace App\Services\AuthServices;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

class AuthService
{
    public function login(string $username, string $password): ?array
    {
        $user = User::with("role")
            ->where("username", $username)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken("PersonalAccessToken")->plainTextToken;

        return [
            "user" => $user,
            "token" => $token,
            "cookie" => cookie("authcookie", $token),
            "should_change_password" => $username === $password,
        ];
    }

    public function logout(User $user): void
    {
        Cookie::forget("authcookie");
        $user->currentAccessToken()->delete();
    }

    public function changePassword(int $userId, string $newPassword): ?User
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $user->update([
            "password" => Hash::make($newPassword),
        ]);

        return $user;
    }

    public function resetPassword(int $userId): ?User
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $user->update([
            "password" => $user->username,
        ]);

        return $user;
    }
}
