<?php

namespace App\Helpers;

use App\Models\AuditTrail;
use Jenssegers\Agent\Agent;

class AuditTrailHelper
{
    public static function activityLogs(
        $moduleType,
        $moduleName,
        $moduleId,
        $action,
        $newData = null,
        $previousData = null,
        $remarks = null
    ) {
        $user = CodeHelper::getUserData();
        $agent = new Agent();

        AuditTrail::create([
            "module_type" => $moduleType,
            "module_name" => $moduleName,
            "module_id" => $moduleId,
            "action" => $action,
            "action_by" => $user->id,
            "action_by_name" => $user->by,
            "log_info" => "{$action} performed on {$moduleName}",
            "previous_data" => $previousData
                ? json_encode($previousData)
                : null,
            "new_data" => $newData ? json_encode($newData) : null,
            "remarks" => $remarks,
            "ip_address" => request()->ip(),
            "user_agent" =>
                $agent->browser() ??
                (request()->header("User-Agent") ?? "Unknown"),
        ]);
    }
}
