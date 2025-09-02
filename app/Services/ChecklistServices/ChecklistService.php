<?php

namespace App\Services\ChecklistServices;

use App\Models\Checklist;

class ChecklistService
{
    public function createChecklist(array $data): array
    {
        $new_checklist = Checklist::create([
            "name" => $data["name"],
        ]);

        return ["checklist" => $new_checklist];
    }

    public function updateChecklist(int $checklistId, array $data): array
    {
        $checklist = Checklist::find($checklistId);

        if (!$checklist) {
            return null;
        }

        $checklist->name = $data["name"];

        if (!$checklist->isDirty()) {
            $message = "No Changes";
        } else {
            $checklist->save();
            $message = "Checklist successfully updated";
        }

        return ["message" => $message, "checklist" => $checklist];
    }

    public function toggleArchived(int $checklistId): array
    {
    }
}
