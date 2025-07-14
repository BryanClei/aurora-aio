<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

Route::post("login", [AuthController::class, "login"]);

Route::group(["middleware" => ["auth:sanctum"]], function () {});
