<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\User\UserManagementController;

Route::post("login", [AuthController::class, "login"]);

Route::middleware(["auth:sanctum"])->group(function () {
    Route::patch("users/{id}/change_password", [
        PasswordController::class,
        "changePassword",
    ]);

    Route::apiResource("users", UserManagementController::class);

    Route::patch("role/{id}/toggle_archived", [
        RoleController::class,
        "toggleArchive",
    ]);
    Route::apiResource("role", RoleController::class);
});
