<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Area\AreaController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\Store\StoreController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Region\RegionController;
use App\Http\Controllers\Api\Checklist\ChecklistController;
use App\Http\Controllers\Api\User\UserManagementController;
use App\Http\Controllers\Api\OneCharging\OneChargingController;

Route::post("login", [AuthController::class, "login"]);

Route::middleware(["auth_key"])->group(function () {
    Route::get("one_charging/api", [OneChargingController::class, "index"]);
    Route::post("one_charging/sync", [OneChargingController::class, "sync"]);
});

Route::middleware(["auth:sanctum"])->group(function () {
    Route::post("logout", [AuthController::class, "logout"]);

    Route::get("one_charging", [OneChargingController::class, "index"]);
    Route::get("one_charging/{id}", [OneChargingController::class, "show"]);
    Route::post("one_charging/system_sync", [
        OneChargingController::class,
        "sync",
    ]);

    // User Controller
    Route::patch("users/{id}/toggle_archived", [
        UserManagementController::class,
        "toggleArchived",
    ]);
    Route::patch("users/change_password", [
        PasswordController::class,
        "changedPassword",
    ]);
    Route::patch("users/{id}/reset_password", [
        PasswordController::class,
        "resetPassword",
    ]);

    Route::get("sedar_employees", [
        UserManagementController::class,
        "sedar_employees",
    ]);

    Route::apiResource("users", UserManagementController::class);

    // Role Controller
    Route::patch("role/{id}/toggle_archived", [
        RoleController::class,
        "toggleArchive",
    ]);
    Route::apiResource("role", RoleController::class);

    // Region Controller
    Route::patch("region/{id}/toggle_archived", [
        RegionController::class,
        "toggleArchive",
    ]);
    Route::apiResource("region", RegionController::class);

    // Area Controller
    Route::patch("area/{id}/toggle_archived", [
        AreaController::class,
        "toggleArchive",
    ]);
    Route::apiResource("area", AreaController::class);

    // Checklist Controller
    Route::apiResource("checklist", ChecklistController::class);

    // Store Controller
    Route::apiResource("store", StoreController::class);
});
