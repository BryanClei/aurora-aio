<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QA\QAController;
use App\Http\Controllers\Api\Area\AreaController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\Store\StoreController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Region\RegionController;
use App\Http\Controllers\Api\Checklist\ChecklistController;
use App\Http\Controllers\Api\PatchNote\PatchNoteController;
use App\Http\Controllers\Api\User\UserManagementController;
use App\Http\Controllers\Api\QA\ApproverDashboardController;
use App\Http\Controllers\Api\Store\StoreChecklistController;
use App\Http\Controllers\Api\Allowable\AllowableDaysController;
use App\Http\Controllers\Api\GradingRule\GradingRuleController;
use App\Http\Controllers\Api\OneCharging\OneChargingController;
use App\Http\Controllers\Api\ScoreRating\ScoreRatingController;
use App\Http\Controllers\Api\RegionAreaHead\RegionAreaHeadController;
use App\Http\Controllers\Api\Report\Export\ExportStoreChecklistController;

Route::post("login", [AuthController::class, "login"]);
Route::get("patch_notes/public_display", [
    PatchNoteController::class,
    "public_index",
]);
Route::get("patch_notes/{patchNote}/public_display", [
    PatchNoteController::class,
    "public_show",
]);

Route::middleware(["auth_key"])->group(function () {
    Route::get("one_charging/api", [OneChargingController::class, "index"]);
    Route::post("one_charging/sync", [OneChargingController::class, "sync"]);

    Route::post("sync_to_one_rdf/one_rdf_user", [OneChargingController::class, "oneRdfUserSync"]);
    Route::patch("sync_to_one_rdf/change_password/{id}", [OneChargingController::class, "changePassword"]);
    Route::patch("sync_to_one_rdf/reset_password/{id}", [OneChargingController::class, "resetPassword"]);
});

Route::middleware(["auth:sanctum"])->group(function () {
    Route::post("logout", [AuthController::class, "logout"]);

    Route::get("one_rdf_user/display", [OneChargingController::class, "oneRdfUserIndex"]);
    Route::get("one_rdf_user/display/{id}", [OneChargingController::class, "oneRdfUserShow"]);
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
    Route::patch("checklist/{id}/toggle_archived", [
        ChecklistController::class,
        "toggleArchive",
    ]);
    Route::apiResource("checklist", ChecklistController::class);

    // Store Controller
    Route::patch("store/{id}/toggle_archived", [
        StoreController::class,
        "toggleArchived",
    ]);
    Route::apiResource("store", StoreController::class);

    // Store Checklist Controller
    Route::patch("store_checklist/{id}/toggle_archived", [
        StoreChecklistController::class,
        "toggleArchived",
    ]);
    Route::apiResource("store_checklist", StoreChecklistController::class);

    // Patch Notes Controller
    Route::get("patch_notes/download/{filename}", [
        PatchNoteController::class,
        "download",
    ]);
    Route::patch("patch_notes/{id}/publish_update", [
        PatchNoteController::class,
        "publishing_update",
    ]);
    Route::apiResource("patch_notes", PatchNoteController::class);

    // QA Controller
    Route::get("quality_assurance/{id}/filtered_week");
    Route::post("quality_assurance/download/attachments", [
        QAController::class,
        "downloadAttachment",
    ]);
    Route::post("quality_assurance/auto_skip", [
        QAController::class,
        "weeklySkipped",
    ]);
    Route::patch("quality_assurance/{id}/for_approval", [
        QAController::class,
        "forApproval",
    ]);
    Route::get("attachments/view", [
        QAController::class,
        "viewSingleAttachment",
    ]);
    Route::patch("quality_assurance/{id}/add_signature", [
        QAController::class,
        "addSignature",
    ]);
    Route::get("quality_assurance/{id}/view_attachment", [
        QAController::class,
        "viewAttachment",
    ]);
    Route::apiResource("quality_assurance", QAController::class);

    // Region Area Head Controller
    Route::apiResource("region_area_head", RegionAreaHeadController::class);

    // Score Rating Controller
    Route::patch("rating/{id}/toggle_archived", [
        ScoreRatingController::class,
        "toggleArchived",
    ]);
    Route::apiResource("rating", ScoreRatingController::class);

    // Survey Dashboard Controller
    Route::patch("approver_dashboard/{id}/approved", [
        ApproverDashboardController::class,
        "approved",
    ]);
    Route::patch("approver_dashboard/{id}/rejected", [
        ApproverDashboardController::class,
        "rejected",
    ]);
    Route::get("approver_dashboard/badge_count", [
        ApproverDashboardController::class,
        "badgeCount",
    ]);
    Route::apiResource(
        "approver_dashboard",
        ApproverDashboardController::class
    );

    Route::get("export/region/area/store_grades", [
        ExportStoreChecklistController::class,
        "storeGradesExport",
    ]);

    Route::get("export/region/area/store_grades/per_week", [
        ExportStoreChecklistController::class,
        "storeAreaPerWeekExport",
    ]);

    Route::apiResource("grade_rule", GradingRuleController::class);

    Route::apiResource("allowable_days", AllowableDaysController::class);
});

// Auth Debug Route - Remove or protect this in production!
// Route::get('/debug-header', function (Request $request) {
//     $debugData = [
//         // Laravel request
//         'laravel' => [
//             'authorization_header' => $request->header('Authorization'),
//             'bearer_token'         => $request->bearerToken(),
//             'all_headers'          => $request->headers->all(),
//         ],

//         // Raw server vars
//         'server' => [
//             'HTTP_AUTHORIZATION'          => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
//             'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
//             'HTTP_X_AUTHORIZATION'        => $_SERVER['HTTP_X_AUTHORIZATION'] ?? null,
//             'HTTP_X_AUTH_TOKEN'           => $_SERVER['HTTP_X_AUTH_TOKEN'] ?? null,
//             'REQUEST_SCHEME'              => $_SERVER['REQUEST_SCHEME'] ?? null,
//             'SERVER_PORT'                 => $_SERVER['SERVER_PORT'] ?? null,
//             'HTTPS'                       => $_SERVER['HTTPS'] ?? null,
//             'HTTP_X_FORWARDED_FOR'        => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
//             'HTTP_X_FORWARDED_PROTO'      => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
//         ],

//         // All $_SERVER keys that contain "auth" (case-insensitive)
//         'server_auth_keys' => array_filter(
//             $_SERVER,
//             fn($key) => str_contains(strtolower($key), 'auth'),
//             ARRAY_FILTER_USE_KEY
//         ),

//         // All $_SERVER keys that contain "http" (catch any proxy headers)
//         'server_http_keys' => array_filter(
//             $_SERVER,
//             fn($key) => str_contains(strtolower($key), 'http'),
//             ARRAY_FILTER_USE_KEY
//         ),

//         // Apache headers (if available)
//         'apache_headers' => function_exists('apache_request_headers')
//             ? apache_request_headers()
//             : 'apache_request_headers() not available',

//         // getallheaders fallback
//         'getallheaders' => function_exists('getallheaders')
//             ? getallheaders()
//             : 'getallheaders() not available',
//     ];

//     // Log everything
//     \Illuminate\Support\Facades\Log::debug('=== AUTH DEBUG START ===');
//     \Illuminate\Support\Facades\Log::debug('Laravel Headers',      $debugData['laravel']);
//     \Illuminate\Support\Facades\Log::debug('Server Vars',          $debugData['server']);
//     \Illuminate\Support\Facades\Log::debug('Server AUTH keys',     $debugData['server_auth_keys']);
//     \Illuminate\Support\Facades\Log::debug('Server HTTP keys',     $debugData['server_http_keys']);
//     \Illuminate\Support\Facades\Log::debug('Apache Headers',       is_array($debugData['apache_headers'])
//         ? $debugData['apache_headers']
//         : ['result' => $debugData['apache_headers']]);
//     \Illuminate\Support\Facades\Log::debug('=== AUTH DEBUG END ===');

//     return response()->json($debugData, 200, [], JSON_PRETTY_PRINT);
// });
