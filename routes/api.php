<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\User\UserManagementController;
use App\Http\Controllers\Api\OneCharging\OneChargingController;

Route::post("login", [AuthController::class, "login"]);

Route::middleware(["auth_key"])->group(function () {
    Route::get("one_charging", [OneChargingController::class, "index"]);
    Route::post("one_charging/sync", [OneChargingController::class, "sync"]);
});

Route::middleware(["auth:sanctum"])->group(function () {
    // User Controller
    Route::patch("users/change_password", [
        PasswordController::class,
        "changedPassword",
    ]);
    Route::patch("users/{id}/reset_password", [
        PasswordController::class,
        "resetPassword",
    ]);
    Route::apiResource("users", UserManagementController::class);

    // Role Controller
    Route::patch("role/{id}/toggle_archived", [
        RoleController::class,
        "toggleArchive",
    ]);
    Route::apiResource("role", RoleController::class);
});
