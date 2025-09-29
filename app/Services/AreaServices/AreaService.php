<?php

namespace App\Services\AreaServices;

use App\Models\Area;
use App\Models\Store;

class AreaService
{
    public function createArea(array $data): array
    {
        $area = Area::create([
            "name" => $data["name"],
            "region_id" => $data["region_id"],
            "area_head_id" => $data["area_head_id"],
        ]);

        return ["area" => $area];
    }

    public function updateArea(int $areaId, array $data): ?array
    {
        $area = Area::find($areaId);

        if (!$area) {
            return null;
        }

        $area->name = $data["name"];
        $area->region_id = $data["region_id"];
        $area->area_head_id = $data["area_head_id"];

        if (!$area->isDirty()) {
            $message = "No Changes";
        } else {
            $area->save();
            $message = "Area successfully updated.";
        }

        return ["data" => $area, "message" => $message];
    }

    public function toggleArchived(int $areaId): ?array
    {
        $area = Area::withTrashed()->find($areaId);

        if (!$area) {
            return null;
        }

        if ($area->trashed()) {
            $area->restore();
            $message = __("messages.success_restored", ["attribute" => "Area"]);

            return [
                "success" => true,
                "message" => $message,
                "area" => $area,
            ];
        }

        if (Store::where("area_id", $area->id)->exists()) {
            return [
                "success" => false,
                "message" => "Unable to archive. Area is currently in use.",
                "area" => $area,
            ];
        }

        $area->delete();
        $message = __("messages.success_archived", ["attribute" => "Area"]);

        return [
            "success" => true,
            "message" => $message,
            "area" => $area,
        ];
    }
}
