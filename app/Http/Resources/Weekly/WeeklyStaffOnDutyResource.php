<?php

namespace App\Http\Resources\Weekly;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyStaffOnDutyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "store_checklist_id" => $this->store_checklist_id,
            "staff_id" => json_decode($this->staff_id, true),
            "staff_name" => json_decode($this->staff_name, true),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
