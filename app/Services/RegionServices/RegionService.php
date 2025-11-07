<?php

namespace App\Services\RegionServices;

use App\Models\Area;
use App\Models\Region;
use App\Helpers\AuditTrailHelper;

class RegionService
{
    public function createRegion(array $data): array
    {
        $new_region = Region::create([
            "name" => $data["name"],
            "region_head_id" => $data["region_head_id"],
        ]);

        AuditTrailHelper::activityLogs(
            moduleType: "Masterlist",
            moduleName: "Region",
            moduleId: $new_region->id,
            action: "Create",
            newData: $new_region,
            previousData: null,
            remarks: "New region successfully created."
        );

        return ["region" => $new_region];
    }

    public function updateRegion(int $regionId, array $data): array
    {
        $region = Region::find($regionId);

        if (!$region) {
            return null;
        }

        $region->name = $data["name"];
        $region->region_head_id = $data["region_head_id"];

        if (!$region->isDirty()) {
            $message = "No Changes";
        } else {
            $region->save();
            $message = "Region successfully updated";
        }

        return ["message" => $message, "region" => $region];
    }

    public function toggleArchived(int $regionId): ?array
    {
        $region = Region::withTrashed()->find($regionId);

        if (!$region) {
            return null;
        }

        if ($region->trashed()) {
            $region->restore();
            $message = __("messages.success_restored", [
                "attribute" => "Region",
            ]);

            return [
                "success" => true,
                "message" => $message,
                "region" => $region,
            ];
        }

        if (Area::where("region_id", $region->id)->exists()) {
            return [
                "success" => false,
                "message" => "Unable to archive. Region is currently in use.",
                "region" => $region,
            ];
        }

        $region->delete();
        $message = __("messages.success_archived", [
            "attribute" => "Region",
        ]);

        return ["success" => true, "message" => $message, "region" => $region];
    }
}
