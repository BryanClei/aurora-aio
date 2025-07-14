<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Role\RoleController;

Route::post("login", [AuthController::class, "login"]);

Route::middleware(["auth:sanctum"])->group(function () {
    Route::apiResource("role", RoleController::class);
});
