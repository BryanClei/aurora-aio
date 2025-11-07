<?php

namespace App\Services\StoreServices;

use App\Models\StoreChecklist;

class StoreChecklistService
{
    public static function createStoreChecklist(array $data): array
    {
        $store_checklist = StoreChecklist::create([
            "store_id" => $data["store_id"],
            "checklist_id" => $data["checklist_id"],
            "status" => "Active",
        ]);

        return [
            "store_checklist" => $store_checklist,
        ];
    }

    public static function updateStoreChecklist(int $id, array $data): array
    {
        $store_checklist = StoreChecklist::findOrFail($id);

        $store_checklist->fill([
            "store_id" => $data["store_id"],
            "checklist_id" => $data["checklist_id"],
        ]);

        if ($store_checklist->isDirty(["store_id", "checklist_id"])) {
            $store_checklist->save();
            $message = "Store Checklist successfully updated";
        } else {
            $message = "No changes were made.";
        }

        return [
            "store_checklist" => $store_checklist,
            "message" => $message,
        ];
    }

    public static function toggleArchived(int $id): array
    {
        $store_checklist = StoreChecklist::withTrashed()->findOrFail($id);

        if (is_null($store_checklist->deleted_at)) {
            $store_checklist->delete();
            $store_checklist->status = "Archived";
            $store_checklist->save();
            $message = "Store Checklist successfully archived";
        } else {
            $store_checklist->restore();
            $store_checklist->status = "Active";
            $store_checklist->save();
            $message = "Store Checklist successfully restored";
        }

        return [
            "store_checklist" => $store_checklist,
            "message" => $message,
        ];
    }
}
